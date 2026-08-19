<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ArchiveOldRecordsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour max for big archiving jobs

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $limitDate = now()->subMonths(6);
        $chunkSize = 500;

        // 1. Arquivar Apontamentos e seus auxiliares extras
        DB::table('apontamentos')
            ->where('created_at', '<', $limitDate)
            ->orderBy('id')
            ->chunk($chunkSize, function ($apontamentos) {
                DB::transaction(function () use ($apontamentos) {
                    foreach ($apontamentos as $ap) {
                        // Snapshot dos relacionamentos
                        $snapshot = [
                            'colaborador_nome' => DB::table('produtividade_colaborador')->where('id', $ap->colaborador_id)->value('nome_completo') ?? 'Desconhecido',
                            'projeto_nome' => $ap->projeto_id ? (DB::table('produtividade_projeto')->where('id', $ap->projeto_id)->value('nome') ?? 'Desconhecido') : null,
                            'cliente_nome' => $ap->codigo_cliente_id ? (DB::table('produtividade_codigocliente')->where('id', $ap->codigo_cliente_id)->value('nome') ?? 'Desconhecido') : null,
                            'centro_custo_nome' => $ap->centro_custo_id ? (DB::table('produtividade_centrocusto')->where('id', $ap->centro_custo_id)->value('nome') ?? 'Desconhecido') : null,
                            'veiculo_placa' => $ap->veiculo_id ? (DB::table('produtividade_veiculo')->where('id', $ap->veiculo_id)->value('placa') ?? 'Desconhecido') : null,
                            'auxiliar_nome' => $ap->auxiliar_id ? (DB::table('produtividade_colaborador')->where('id', $ap->auxiliar_id)->value('nome_completo') ?? 'Desconhecido') : null,
                            'registrado_por_nome' => $ap->registrado_por_id ? (DB::table('users')->where('id', $ap->registrado_por_id)->value('name') ?? 'Desconhecido') : null,
                            'aprovador_nome' => $ap->aprovador_id ? (DB::table('users')->where('id', $ap->aprovador_id)->value('name') ?? 'Desconhecido') : null,
                        ];

                        $apArchiveData = (array) $ap;
                        $apArchiveData['snapshot_dados'] = json_encode($snapshot);

                        DB::table('apontamentos_archive')->insert($apArchiveData);

                        // Arquivar e deletar auxiliares extras
                        $auxiliaresExtras = DB::table('apontamento_auxiliar_extra')->where('apontamento_id', $ap->id)->get();
                        foreach ($auxiliaresExtras as $aux) {
                            $snapshotAux = [
                                'colaborador_nome' => DB::table('produtividade_colaborador')->where('id', $aux->colaborador_id)->value('nome_completo') ?? 'Desconhecido',
                            ];
                            $auxArchiveData = (array) $aux;
                            $auxArchiveData['snapshot_dados'] = json_encode($snapshotAux);
                            
                            DB::table('apontamento_auxiliar_extra_archive')->insert($auxArchiveData);
                        }
                    }

                    $ids = $apontamentos->pluck('id')->toArray();
                    
                    // Exclui a tabela pivô e em seguida o pai
                    DB::table('apontamento_auxiliar_extra')->whereIn('apontamento_id', $ids)->delete();
                    DB::table('apontamentos')->whereIn('id', $ids)->delete();
                });
            });

        // 2. Arquivar Notificações
        DB::table('notificacoes')
            ->where('created_at', '<', $limitDate)
            ->orderBy('id')
            ->chunk($chunkSize, function ($notificacoes) {
                DB::transaction(function () use ($notificacoes) {
                    foreach ($notificacoes as $not) {
                        $snapshot = [
                            'colaborador_nome' => DB::table('produtividade_colaborador')->where('id', $not->colaborador_id)->value('nome_completo') ?? 'Desconhecido',
                            'remetente_nome' => $not->remetente_id ? (DB::table('users')->where('id', $not->remetente_id)->value('name') ?? 'Desconhecido') : null,
                        ];

                        $notArchiveData = (array) $not;
                        $notArchiveData['snapshot_dados'] = json_encode($snapshot);

                        DB::table('notificacoes_archive')->insert($notArchiveData);
                    }

                    $ids = $notificacoes->pluck('id')->toArray();
                    DB::table('notificacoes')->whereIn('id', $ids)->delete();
                });
            });

        // 3. Arquivar Log Auditorias (Atenção: usando data_hora)
        DB::table('log_auditorias')
            ->where('data_hora', '<', $limitDate)
            ->orderBy('id')
            ->chunk($chunkSize, function ($logs) {
                DB::transaction(function () use ($logs) {
                    foreach ($logs as $log) {
                        $snapshot = [
                            'user_nome' => $log->user_id ? (DB::table('users')->where('id', $log->user_id)->value('name') ?? 'Desconhecido') : null,
                        ];

                        $logArchiveData = (array) $log;
                        $logArchiveData['snapshot_dados'] = json_encode($snapshot);

                        DB::table('log_auditorias_archive')->insert($logArchiveData);
                    }

                    $ids = $logs->pluck('id')->toArray();
                    DB::table('log_auditorias')->whereIn('id', $ids)->delete();
                });
            });
    }
}
