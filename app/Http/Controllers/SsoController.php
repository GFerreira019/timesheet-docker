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
            $user = User::where('id_usuario_erp', $dadosUsuario['id_usuario'])->first();

            if (!$user) {
                // Usuário não existe, vamos criar um
                $user = User::create([
                    'id_usuario_erp' => $dadosUsuario['id_usuario'],
                    'name' => $dadosUsuario['nome'] ?? 'Sem Nome',
                    'email' => $dadosUsuario['email'] ?? null,
                    'password' => bcrypt(Str::random(24)),
                ]);
                $user->assignRole('OPERACIONAL');
            } else {
                // Usuário existe, verifica se tem role
                if ($user->roles()->count() === 0) {
                    $user->assignRole('OPERACIONAL');
                }
                
                // Opcional: Atualiza os dados básicos se eles mudaram
                $updateData = [];
                if (isset($dadosUsuario['nome']) && $user->name !== $dadosUsuario['nome']) {
                    $updateData['name'] = $dadosUsuario['nome'];
                }
                if (isset($dadosUsuario['email']) && $user->email !== $dadosUsuario['email']) {
                    $updateData['email'] = $dadosUsuario['email'];
                }
                if (!empty($updateData)) {
                    $user->update($updateData);
                }
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
