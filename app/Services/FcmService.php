<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Injetamos a dependência da biblioteca do Firebase
     */
    public function __construct(
        protected Messaging $messaging
    ) {}

    /**
     * Envia notificação Push para um Token específico.
     *
     * @param string $token Token do dispositivo
     * @param string $title Título da Push
     * @param string $body Corpo da Push
     * @param array $data Payload invisível para tratamento no app nativo (opcional)
     * @return array|null Retorna os metadados do disparo ou nulo em caso de falha
     */
    public function sendNotification(string $token, string $title, string $body, array $data = []): ?array
    {
        if (empty($token)) {
            return null; // Usuário não tem o app instalado ou não autorizou push
        }

        try {
            // Constrói a mensagem HTTP v1 do FCM na sintaxe mais recente (v8+)
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data, // Data payload (ex: ['action' => 'aprovar_hora', 'id' => 10])
            ]);

            // Realiza o disparo
            return $this->messaging->send($message);
            
        } catch (\Throwable $e) {
            // Em caso de erro (ex: credenciais falhas ou token expirado), deixamos um Log limpo
            Log::error('FALHA_ENVIO_FCM', [
                'token' => $token,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }
}
