<?php

namespace App\Observers;

use App\Models\Notificacao;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class NotificacaoObserver
{
    public function __construct(
        protected FcmService $fcmService
    ) {}

    /**
     * Handle the Notificacao "created" event.
     */
    public function created(Notificacao $notificacao): void
    {
        try {
            // Recupera o usuário associado a esta notificação
            $user = $notificacao->colaborador?->user;

            // Verifica se o usuário existe e se possui um fcm_token preenchido
            if ($user && !empty($user->fcm_token)) {
                $data = ['screen' => 'notificacoes'];

                // Dispara o Push utilizando o título e a mensagem da notificação
                $this->fcmService->sendNotification(
                    $user->fcm_token,
                    $notificacao->titulo,
                    $notificacao->mensagem,
                    $data
                );
            }
        } catch (\Exception $e) {
            // Regra de Ouro (Blindagem): Loga o erro mas não quebra o fluxo de criação da notificação
            report($e);
        }
    }
}
