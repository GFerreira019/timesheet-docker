<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function callback(Request $request)
    {
        $ticket = $request->query('ticket');

        if (!$ticket) {
            abort(400, 'Ticket não fornecido.');
        }

        $erpUrlBase = config('services.erp.url');
        $erpKey = config('services.erp.key');

        if (!$erpUrlBase) {
            return redirect()->route('login')->withErrors(['error' => 'URL do ERP não configurada.']);
        }

        $endpoint = rtrim($erpUrlBase, '/') . '/sso-ticket.php';

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Key' => $erpKey])
                ->post($endpoint, [
                    'ticket' => $ticket
                ]);

            if ($response->failed()) {
                Log::warning('SSO: Erro HTTP ao validar ticket no ERP.', ['status' => $response->status()]);
                return redirect()->route('login')->withErrors(['error' => 'Acesso negado pelo ERP (Falha de comunicação).']);
            }

            $data = $response->json();

            // Verifica acesso_liberado
            if (!isset($data['acesso_liberado']) || $data['acesso_liberado'] !== true) {
                return redirect()->route('login')->withErrors(['error' => 'Acesso negado pelo ERP.']);
            }

            $dadosUsuario = $data['data'] ?? $data; // Handle structure variation (data wrap vs root)

            if (!isset($dadosUsuario['id_usuario'])) {
                Log::warning('SSO: Dados do usuário incompletos retornados pelo ERP.');
                return redirect()->route('login')->withErrors(['error' => 'Dados de usuário inválidos retornados pelo ERP.']);
            }

            // Just-In-Time Provisioning
            $user = User::firstOrNew(['connect_user_id' => $dadosUsuario['id_usuario']]);

            // Atualiza o nome APENAS no primeiro acesso (quando o ID ainda não existe)
            if (!$user->exists) {
                $user->name = $dadosUsuario['nome'] ?? 'Sem Nome';
                $user->password = bcrypt(Str::random(24));
            }

            // Dados que devem ser atualizados em TODO login
            $user->email = $dadosUsuario['email'] ?? $user->email;
            $user->solides_id = $dadosUsuario['solides_id'] ?? $user->solides_id;
            
            if (isset($dadosUsuario['is_superuser'])) {
                $user->is_superuser = filter_var($dadosUsuario['is_superuser'], FILTER_VALIDATE_BOOLEAN);
            }
            
            $user->save();

            // Sincroniza a Role (Spatie Permission) com mapeamento seguro
            $roleMap = [
                '1' => 'ADMIN', 'administrador' => 'ADMIN',
                '2' => 'GERENCIAL', 'gerente' => 'GERENCIAL',
                '3' => 'SAC', 'assistente' => 'SAC',
                '4' => 'COORDENADOR', 'coordenadores' => 'COORDENADOR',
                '5' => 'OPERACIONAL', 'operacional' => 'OPERACIONAL',
            ];

            $valorApi = $dadosUsuario['nivel_planejamento'] ?? null;
            $chaveBusca = $valorApi ? strtolower(trim((string) $valorApi)) : null;

            if ($chaveBusca && array_key_exists($chaveBusca, $roleMap)) {
                $roleSpatie = $roleMap[$chaveBusca];
            } else {
                $roleSpatie = 'OPERACIONAL';
                \Log::warning("SSO: Nível de planejamento desconhecido ou vazio (" . ($valorApi ?: 'NULO') . ") para o usuário {$user->id}. Fallback para OPERACIONAL aplicado.");
            }

            if (\Spatie\Permission\Models\Role::where('name', $roleSpatie)->exists()) {
                $user->syncRoles([$roleSpatie]);
            } else {
                \Log::error("SSO: A Role '{$roleSpatie}' não existe no banco de dados. Sincronização ignorada para o usuário {$user->id}.");
            }

            // Loga o usuário no Laravel
            Auth::login($user);

            // Redireciona para o painel / dashboard
            return redirect()->route('painel');

        } catch (\Exception $e) {
            report($e);
            return redirect()->route('login')->withErrors(['error' => 'Erro interno ao validar o acesso.']);
        }
    }

    public function connect(Request $request)
    {
        $ticket = (string) $request->query('ticket');

        if (!$ticket) {
            return redirect('/login');
        }

        try {
            $r = Http::withHeaders(['X-Api-Key' => config('services.erp.key')])
                ->acceptJson()
                ->timeout(10)
                ->post('https://atgbconnect.com.br/api/v1/sso-ticket-timesheet.php', [
                    'ticket' => $ticket,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[sso-connect] resgate falhou: ' . $e->getMessage());
            return redirect('/login')->withErrors(['error' => 'Falha de comunicação com o ERP (Timeout ou Indisponibilidade).']);
        }

        $json = $r->json();
        if (! $r->successful() || ! ($json['success'] ?? false)) {
            $erroApi = $json['error'] ?? $r->status();
            Log::info('[sso-connect] recusado: ' . $erroApi);
            return redirect('/login')->withErrors(['error' => 'Ticket de acesso recusado pelo ERP. Motivo: ' . $erroApi]);
        }

        $u = $json['data'] ?? [];
        if (($u['acesso_liberado'] ?? false) !== true) {
            return redirect('/login')->withErrors(['error' => 'Seu usuário não possui a flag "acesso_liberado" ativa no ERP.']);
        }

        $user = User::firstOrNew(['email' => $u['email']]);
        $user->name = $u['nome'];
        $user->connect_user_id = $u['id_usuario'];
        $user->save();

        // Sincroniza a Role (Spatie Permission) com mapeamento seguro
        $roleMap = [
            '1' => 'ADMIN', 'administrador' => 'ADMIN',
            '2' => 'GERENCIAL', 'gerente' => 'GERENCIAL',
            '3' => 'SAC', 'assistente' => 'SAC',
            '4' => 'COORDENADOR', 'coordenadores' => 'COORDENADOR',
            '5' => 'OPERACIONAL', 'operacional' => 'OPERACIONAL',
        ];

        $valorApi = $u['nivel_planejamento'] ?? null;
        $chaveBusca = $valorApi ? strtolower(trim((string) $valorApi)) : null;

        if ($chaveBusca && array_key_exists($chaveBusca, $roleMap)) {
            $roleSpatie = $roleMap[$chaveBusca];
        } else {
            $roleSpatie = 'OPERACIONAL';
            \Log::warning("SSO: Nível de planejamento desconhecido ou vazio (" . ($valorApi ?: 'NULO') . ") para o usuário {$user->id}. Fallback para OPERACIONAL aplicado.");
        }

        if (\Spatie\Permission\Models\Role::where('name', $roleSpatie)->exists()) {
            $user->syncRoles([$roleSpatie]);
        } else {
            \Log::error("SSO: A Role '{$roleSpatie}' não existe no banco de dados. Sincronização ignorada para o usuário {$user->id}.");
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }
}
