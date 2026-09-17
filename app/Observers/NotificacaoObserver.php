<?php

namespace App\Observers;

use App\Jobs\ProcessOmnichannelNotificationJob;
use App\Models\Notificacao;

class NotificacaoObserver
{
    /**
     * Handle the Notificacao "creating" event.
     */
    public function creating(Notificacao $notificacao)
    {
        // Apenas notificações automáticas são bloqueadas. Notificações manuais (INFO) passam.
        if ($notificacao->tipo !== 'INFO') {
            $notificacao->loadMissing('colaborador');
            if ($notificacao->colaborador && $notificacao->colaborador->recebe_notificacao === false) {
                return false; // Trava a criação no banco de dados e consequentemente o disparo do Job
            }
        }
    }

    /**
     * Handle the Notificacao "created" event.
     */
    public function created(Notificacao $notificacao): void
    {
        // Dispara o job de notificação para a fila (FCM), 
        // liberando a thread principal e evitando gargalos de I/O de rede.
        ProcessOmnichannelNotificationJob::dispatch($notificacao);
    }
}
