<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Logout;

/**
 * Listener: RegistrarLogout
 *
 * Equivalente ao signal Django:
 *   @receiver(user_logged_out)
 *   def log_logout(sender, request, user, **kwargs):
 *       if user:
 *           LogAuditoria.objects.create(
 *               usuario=user, acao='LOGOUT', modelo_afetado='Sistema',
 *               detalhes="Logout efetuado com sucesso.",
 *               ip_address=get_client_ip(request)
 *           )
 *
 * Disparado pelo evento Illuminate\Auth\Events\Logout do Laravel.
 * Verifica se o usuário existe antes de registrar (equivalente ao `if user:` do Django).
 */
class RegistrarLogout
{
    /**
     * @param Logout $event  O evento de logout do Laravel
     */
    public function handle(Logout $event): void
    {
        try {
            // Equivalente ao `if user:` do Django — o evento sempre tem $event->user
            if (!$event->user) {
                return;
            }

            AuditoriaService::registrar(
                request: request(),
                acao: 'LOGOUT',
                modelo: 'Sistema',
                objId: null,
                detalhes: 'Logout efetuado com sucesso.'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("FALHA AUDITORIA (LOGOUT): {$e->getMessage()}");
        }
    }
}
