<?php

namespace App\Jobs;

use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\Notificacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPendingApprovalNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Apontamento $apontamento,
        public Colaborador $gestor
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $primeiroNome = 'Colaborador';
        if ($this->apontamento->colaborador && $this->apontamento->colaborador->nome_completo) {
            $primeiroNome = explode(' ', $this->apontamento->colaborador->nome_completo)[0];
        }

        $dataApontamento = $this->apontamento->data_apontamento ? $this->apontamento->data_apontamento->format('d/m/Y') : 'Data não informada';

        Notificacao::create([
            'colaborador_id' => $this->gestor->id,
            'titulo'         => 'Aprovação Pendente',
            'mensagem'       => "O apontamento de {$primeiroNome} em {$dataApontamento} está aguardando sua aprovação.",
            'tipo'           => 'ALERTA',
            'lida'           => false,
            'apontamento_id' => $this->apontamento->id,
            'data_referencia' => $this->apontamento->data_apontamento,
        ]);
    }
}
