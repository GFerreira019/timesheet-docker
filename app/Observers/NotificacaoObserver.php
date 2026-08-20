<?php

namespace App\Observers;

use App\Jobs\ProcessOmnichannelNotificationJob;
use App\Models\Notificacao;

class NotificacaoObserver
{
    /**
     * Handle the Notificacao "created" event.
     */
    public function created(Notificacao $notificacao): void
    {
        // Dispara o job de notificação para a fila (FCM / WhatsApp), 
        // liberando a thread principal e evitando gargalos de I/O de rede.
        ProcessOmnichannelNotificationJob::dispatch($notificacao);
    }
}
