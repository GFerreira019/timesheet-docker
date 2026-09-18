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
            \Log::info('SSO [callback] Response Data:', ['payload' => $data]);

            // Validação preventiva adicional
            if (empty($data) || !is_array($data)) {
                Log::error('SSO: Payload vazio ou inválido retornado pelo ERP no callback.');
                return redirect()->route('login')->withErrors(['error' => 'Falha na autenticação: O ERP não retornou dados válidos.']);
            }

            // Verifica acesso_liberado (na raiz ou dentro de data)
            $acesso = $data['acesso_liberado'] ?? ($data['data']['acesso_liberado'] ?? null);
            if ($acesso !== true) {
                return redirect()->route('login')->withErrors(['error' => 'Acesso negado pelo ERP.']);
            }

            $dadosUsuario = $data['data'] ?? $data; // Handle structure variation (data wrap vs root)
            if (isset($dadosUsuario[0]) && is_array($dadosUsuario[0])) {
                $dadosUsuario = $dadosUsuario[0];
            }
            \Log::info('SSO [callback] Dados Extraídos:', ['extracted' => $dadosUsuario]);

            if (empty($dadosUsuario) || empty($dadosUsuario['id_usuario']) || empty($dadosUsuario['email'])) {
                Log::warning('SSO: Dados do usuário incompletos retornados pelo ERP.', ['extracted' => $dadosUsuario]);
                return redirect()->route('login')->withErrors(['error' => 'Dados de usuário inválidos ou incompletos retornados pelo ERP.']);
            }

            // PASSO 2: Buscar Perfil Completo
            $idUsuario = $dadosUsuario['id_usuario'];
            $perfilData = [];
            
            if ($idUsuario) {
                $responsePerfil = \Illuminate\Support\Facades\Http::withHeaders([
                    'accept' => 'application/json',
                    'X-Api-Key' => $erpKey
                ])->get(rtrim($erpUrlBase, '/') . "/usuarios.php", [
                    'id' => $idUsuario,
                    'status' => 1,
                    'limit' => 1,
                    'offset' => 0
                ]);
            
                if ($responsePerfil->successful()) {
                    $perfilData = $responsePerfil->json('data') ?? [];
                    if (isset($perfilData[0]) && is_array($perfilData[0])) {
                        $perfilData = $perfilData[0]; // Previne erro se voltar lista
                    }
                } else {
                    \Log::error("SSO: Falha ao buscar perfil do id_usuario {$idUsuario}", ['status' => $responsePerfil->status()]);
                }
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
            $user->solides_id = $perfilData['tangerino_employee_id'] ?? ($dadosUsuario['tangerino_employee_id'] ?? $user->solides_id);
            
            if (isset($perfilData['is_superuser']) || isset($dadosUsuario['is_superuser'])) {
                $user->is_superuser = filter_var($perfilData['is_superuser'] ?? $dadosUsuario['is_superuser'], FILTER_VALIDATE_BOOLEAN);
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

            $valorApi = $perfilData['nivel_planejamento'] ?? ($dadosUsuario['nivel_planejamento'] ?? null);
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

            // Captura e salva o id_departamento na sessão
            $idDepartamento = $perfilData['id_departamento'] ?? ($dadosUsuario['id_departamento'] ?? null);
            if ($idDepartamento) {
                session(['id_departamento' => $idDepartamento]);
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
        \Log::info('SSO [connect] Response Data:', ['payload' => $json]);
        
        if (empty($json) || !is_array($json)) {
            Log::error('[sso-connect] Payload vazio ou inválido retornado pelo ERP.', ['status' => $r->status()]);
            return redirect('/login')->withErrors(['error' => 'Falha na autenticação: O ERP não retornou dados válidos (Payload vazio).']);
        }

        if (! $r->successful() || ! ($json['success'] ?? false)) {
            $erroApi = $json['error'] ?? $r->status();
            Log::info('[sso-connect] recusado: ' . $erroApi);
            return redirect('/login')->withErrors(['error' => 'Ticket de acesso recusado pelo ERP. Motivo: ' . $erroApi]);
        }

        $u = $json['data'] ?? [];
        if (isset($u[0]) && is_array($u[0])) {
            $u = $u[0];
        }
        \Log::info('SSO [connect] Dados Extraídos:', ['extracted' => $u]);

        // Validação preventiva: não seguir com JIT Provisioning sem os dados essenciais
        if (empty($u) || empty($u['email']) || empty($u['id_usuario'])) {
            Log::error('[sso-connect] Dados obrigatórios (email, id_usuario) ausentes no payload.', ['extracted' => $u]);
            return redirect('/login')->withErrors(['error' => 'Dados de usuário incompletos ou ausentes no retorno do ERP.']);
        }

        $acesso = $json['acesso_liberado'] ?? ($u['acesso_liberado'] ?? false);
        if ($acesso !== true) {
            return redirect('/login')->withErrors(['error' => 'Seu usuário não possui a flag "acesso_liberado" ativa no ERP.']);
        }

        // PASSO 2: Buscar Perfil Completo
        $idUsuario = $u['id_usuario'];
        $perfilData = [];
        
        if ($idUsuario) {
            $responsePerfil = \Illuminate\Support\Facades\Http::withHeaders([
                'accept' => 'application/json',
                'X-Api-Key' => config('services.erp.key')
            ])->get(rtrim(config('services.erp.url'), '/') . "/usuarios.php", [
                'id' => $idUsuario,
                'status' => 1,
                'limit' => 1,
                'offset' => 0
            ]);
        
            if ($responsePerfil->successful()) {
                $perfilData = $responsePerfil->json('data') ?? [];
                if (isset($perfilData[0]) && is_array($perfilData[0])) {
                    $perfilData = $perfilData[0];
                }
            } else {
                \Log::error("SSO: Falha ao buscar perfil do id_usuario {$idUsuario}", ['status' => $responsePerfil->status()]);
            }
        }

        $user = User::firstOrNew(['email' => $u['email']]);
        $user->name = $u['nome'] ?? 'Usuário SSO';
        $user->connect_user_id = $u['id_usuario'];
        $user->solides_id = $perfilData['tangerino_employee_id'] ?? ($u['tangerino_employee_id'] ?? $user->solides_id);
        $user->save();

        // Sincroniza a Role (Spatie Permission) com mapeamento seguro
        $roleMap = [
            '1' => 'ADMIN', 'administrador' => 'ADMIN',
            '2' => 'GERENCIAL', 'gerente' => 'GERENCIAL',
            '3' => 'SAC', 'assistente' => 'SAC',
            '4' => 'COORDENADOR', 'coordenadores' => 'COORDENADOR',
            '5' => 'OPERACIONAL', 'operacional' => 'OPERACIONAL',
        ];

        $valorApi = $perfilData['nivel_planejamento'] ?? ($u['nivel_planejamento'] ?? null);
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

        // Captura e salva o id_departamento na sessão
        $idDepartamento = $perfilData['id_departamento'] ?? ($u['id_departamento'] ?? null);
        if ($idDepartamento) {
            session(['id_departamento' => $idDepartamento]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }
}
