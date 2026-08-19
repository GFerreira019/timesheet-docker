<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Login;

/**
 * Listener: RegistrarLogin
 *
 * Equivalente ao signal Django:
 *   @receiver(user_logged_in)
 *   def log_login(sender, request, user, **kwargs):
 *       ip = get_client_ip(request)
 *       user_agent = request.META.get('HTTP_USER_AGENT', 'Desconhecido')
 *       LogAuditoria.objects.create(
 *           usuario=user, acao='LOGIN', modelo_afetado='Sistema',
 *           detalhes=f"Acesso realizado via: {user_agent}", ip_address=ip
 *       )
 *
 * Disparado pelo evento Illuminate\Auth\Events\Login do Laravel,
 * que equivale ao sinal user_logged_in do Django.
 */
class RegistrarLogin
{
    /**
     * @param Login $event  O evento de login do Laravel
     */
    public function handle(Login $event): void
    {
        try {
            // Obtém o request atual do container (não vem direto no event do Laravel)
            $request   = request();
            $userAgent = $request->header('User-Agent', 'Desconhecido');

            AuditoriaService::registrar(
                request: $request,
                acao: 'LOGIN',
                modelo: 'Sistema',
                objId: null,
                detalhes: "Acesso realizado via: {$userAgent}"
            );
        } catch (\Throwable $e) {
            // Nunca deve derrubar o fluxo de login — equivalente ao try/except do Django
            report($e);
        }
    }
}
