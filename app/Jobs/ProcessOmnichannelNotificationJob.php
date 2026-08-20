<?php

namespace App\Jobs;

use App\Models\Notificacao;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOmnichannelNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Notificacao $notificacao
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FcmService $fcmService): void
    {
        try {
            // Recupera o usuário associado a esta notificação
            $user = $this->notificacao->colaborador?->user;

            // Verifica se o usuário existe e se possui um fcm_token preenchido
            if ($user && !empty($user->fcm_token)) {
                $data = ['screen' => 'notificacoes'];

                // Dispara o Push utilizando o título e a mensagem da notificação
                $fcmService->sendNotification(
                    $user->fcm_token,
                    $this->notificacao->titulo,
                    $this->notificacao->mensagem,
                    $data
                );
            }
        } catch (\Throwable $e) {
            // Loga o erro sem quebrar o worker
            Log::error('Erro ao processar notificação Omnichannel: ' . $e->getMessage(), [
                'notificacao_id' => $this->notificacao->id,
                'exception' => $e
            ]);
        }
    }
}
