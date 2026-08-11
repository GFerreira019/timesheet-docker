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
                Log::error('Erro ao validar ticket no ERP.', ['status' => $response->status(), 'body' => $response->body()]);
                return redirect()->route('login')->withErrors(['error' => 'Acesso negado pelo ERP (Falha de comunicação).']);
            }

            $data = $response->json();

            // Verifica acesso_liberado
            if (!isset($data['acesso_liberado']) || $data['acesso_liberado'] !== true) {
                return redirect()->route('login')->withErrors(['error' => 'Acesso negado pelo ERP.']);
            }

            $dadosUsuario = $data['data'] ?? $data; // Handle structure variation (data wrap vs root)

            if (!isset($dadosUsuario['id_usuario'])) {
                Log::error('Dados do usuário incompletos retornados pelo ERP.', ['dados' => $dadosUsuario]);
                return redirect()->route('login')->withErrors(['error' => 'Dados de usuário inválidos retornados pelo ERP.']);
            }

            // Just-In-Time Provisioning
            $user = User::firstOrNew(['id_usuario_erp' => $dadosUsuario['id_usuario']]);

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

            // Sincroniza a Role (Spatie Permission)
            if (!empty($dadosUsuario['nivel_acesso'])) {
                $user->syncRoles([$dadosUsuario['nivel_acesso']]);
            } elseif ($user->roles()->count() === 0) {
                // Fallback caso não venha nivel_acesso e o usuário não tenha nenhuma role
                $user->assignRole('OPERACIONAL');
            }

            // Loga o usuário no Laravel
            Auth::login($user);

            // Redireciona para o painel / dashboard
            return redirect()->route('painel');

        } catch (\Exception $e) {
            Log::error('Exceção ao validar ticket SSO: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['error' => 'Erro interno ao validar o acesso.']);
        }
    }
}
