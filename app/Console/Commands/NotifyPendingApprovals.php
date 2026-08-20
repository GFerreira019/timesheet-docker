<?php

namespace App\Console\Commands;

use App\Models\Apontamento;
use App\Models\Notificacao;
use Illuminate\Console\Command;

class NotifyPendingApprovals extends Command
{
    protected $signature = 'app:notify-pending-approvals';
    protected $description = 'Notifica gestores sobre apontamentos pendentes de aprovação há mais de 24 horas';

    public function handle()
    {
        $this->info('Buscando apontamentos pendentes de aprovação...');

        // O eager loading carrega o colaborador titular e os gestores do projeto.
        $apontamentos = Apontamento::with(['colaborador', 'projeto.gestores'])
            ->where('status_aprovacao', 'EM_ANALISE')
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        $count = 0;

        foreach ($apontamentos as $apontamento) {
            if ($apontamento->projeto && $apontamento->projeto->gestores->isNotEmpty()) {
                
                $primeiroNome = 'Colaborador';
                if ($apontamento->colaborador && $apontamento->colaborador->nome_completo) {
                    $primeiroNome = explode(' ', $apontamento->colaborador->nome_completo)[0];
                }

                $dataApontamento = $apontamento->data_apontamento ? $apontamento->data_apontamento->format('d/m/Y') : 'Data não informada';

                foreach ($apontamento->projeto->gestores as $gestorColaborador) {
                    
                    // Criamos a notificação diretamente no Command, de forma síncrona!
                    // Isso é extremamente rápido no banco e evita sobrecarga de Jobs inúteis na fila.
                    // O Observer atrelado a Notificacao interceptará isso e lidará com os envios lentos (FCM/Wpp).
                    Notificacao::create([
                        'colaborador_id'  => $gestorColaborador->id,
                        'titulo'          => 'Aprovação Pendente',
                        'mensagem'        => "O apontamento de {$primeiroNome} em {$dataApontamento} está aguardando sua aprovação.",
                        'tipo'            => 'ALERTA',
                        'lida'            => false,
                        'apontamento_id'  => $apontamento->id,
                        'data_referencia' => $apontamento->data_apontamento,
                    ]);
                    
                    $count++;
                }
            }
        }

        $this->info("Concluído! Foram geradas {$count} notificações com sucesso.");
    }
}
