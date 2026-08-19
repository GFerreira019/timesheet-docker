<?php

namespace App\Listeners;

use App\Models\LogAuditoria;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

/**
 * Listener: RegistrarFalhaLogin
 *
 * Equivalente ao signal Django:
 *   @receiver(user_login_failed)
 *   def log_login_failed(sender, credentials, request, **kwargs):
 *       ip = get_client_ip(request)
 *       username_tentado = credentials.get('username', 'Desconhecido')
 *       LogAuditoria.objects.create(
 *           usuario=None, acao='LOGIN_FALHA', modelo_afetado='Sistema',
 *           objeto_id=username_tentado,
 *           detalhes=f"Tentativa de login falhou para o usuário: '{username_tentado}'.",
 *           ip_address=ip
 *       )
 *
 * Disparado pelo evento Illuminate\Auth\Events\Failed do Laravel.
 * O usuário é None/null pois a autenticação falhou.
 * O username tentado é registrado como objeto_id (igual ao Django).
 */
class RegistrarFalhaLogin
{
    /**
     * @param Failed $event  O evento de falha de login do Laravel
     */
    public function handle(Failed $event): void
    {
        try {
            $request = request();

            // Equivalente ao credentials.get('username', 'Desconhecido') do Django
            // O Laravel coloca as credenciais no evento $event->credentials
            $usernameTentado = $event->credentials['email']
                ?? $event->credentials['username']
                ?? 'Desconhecido';

            // Cria o log diretamente, pois o usuário é null (falha de auth)
            // Equivalente ao LogAuditoria.objects.create(usuario=None, ...) do Django
            LogAuditoria::create([
                'user_id'        => null,    // sem usuário — tentativa falhou
                'acao'           => 'LOGIN_FALHA',
                'modelo_afetado' => 'Sistema',
                'objeto_id'      => $usernameTentado,  // equivalente ao objeto_id=username_tentado
                'detalhes'       => "Tentativa de login falhou para o usuário: '{$usernameTentado}'.",
                'ip_address'     => AuditoriaService::getClientIp($request),
                'data_hora'      => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
