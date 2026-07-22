<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Apontamento;
use App\Services\ConformidadeCLTService;
use App\Services\AuditoriaService;
use Carbon\Carbon;

class AprovarApontamentosDiarios extends Command
{
    protected $signature = 'timesheet:aprovar-automatico';
    protected $description = 'Aprova automaticamente os apontamentos que estejam em conformidade com as regras da CLT';

    public function handle()
    {
        $this->info('Iniciando aprovação automática...');
        Log::info('Job timesheet:aprovar-automatico iniciado.');
        
        // --- BLOCO DE DIAGNÓSTICO (PRE-QUERY) ---
        $totalPendentes = Apontamento::where('status_aprovacao', 'EM_ANALISE')->count();
        $pendentesHoje = Apontamento::where('status_aprovacao', 'EM_ANALISE')
            ->whereDate('data_apontamento', Carbon::today())
            ->count();
        $pendentesSemCheckout = Apontamento::where('status_aprovacao', 'EM_ANALISE')
            ->whereNull('hora_termino')
            ->count();
            
        $this->line("<fg=cyan>--- DIAGNÓSTICO DE BANCO DE DADOS ---</>");
        $this->line("Total Pendentes (Geral): {$totalPendentes}");
        $this->line("Pendentes de Hoje (Testes locais): {$pendentesHoje}");
        $this->line("Pendentes s/ Checkout (hora_termino nula): {$pendentesSemCheckout}");
        $this->line("<fg=cyan>---------------------------------------</>");
        
        // 1. QUERY SUPER FLEXÍVEL (PERMISSIVA PARA TESTES LOCAIS): 
        // Permitimos <= Carbon::today() para que os dados que você acabou de criar no teste sejam processados.
        $pendentes = Apontamento::where('status_aprovacao', 'EM_ANALISE')
            ->whereDate('data_apontamento', '<=', Carbon::today())
            ->whereNotNull('hora_termino') // Essencial: não aprova quem ainda não deu checkout (saída)
            ->with('colaborador')
            ->get();

        // LOG 1: Quantidade de apontamentos pendentes encontrados
        $this->info("-> Encontrados {$pendentes->count()} apontamentos pendentes para análise de automação.");
        Log::info("Aprovação automática: Encontrados {$pendentes->count()} apontamentos pendentes.");

        if ($pendentes->isEmpty()) {
            $this->warn('Nenhum apontamento apto para aprovação no momento.');
            return;
        }

        $porColaborador = $pendentes->groupBy('colaborador_id');

        $aprovados = 0;
        $barrados = 0;

        foreach ($porColaborador as $colaboradorId => $apontamentos) {
            $colaborador = $apontamentos->first()->colaborador;

            // Extraímos as datas únicas desse colaborador (podem ser de D-1, D-2...)
            $dias = $apontamentos->pluck('data_apontamento')->unique();

            foreach ($dias as $dia) {
                // Roda o motor de conformidade atualizando a flag `flag_atencao` no banco
                ConformidadeCLTService::calcularRegrasClt($colaborador, $dia);
            }

            foreach ($apontamentos as $ap) {
                $ap->refresh();

                if ($ap->flag_atencao) {
                    // LOG 2: Violação encontrada (barrado pela CLT)
                    $this->warn("-> [BARRADO] Apontamento #{$ap->id} de {$colaborador->nome_completo} retido. Motivo: {$ap->motivo_alerta}");
                    Log::warning("Aprovação automática barrada para apontamento #{$ap->id}. Motivo: {$ap->motivo_alerta}");
                    $barrados++;
                    continue; 
                }

                $ap->status_aprovacao = 'APROVADO';
                $ap->motivo_rejeicao  = null;
                // Auditoria de aprovação automática
                $ap->tipo_aprovacao   = 'automatica';
                $ap->aprovador_id     = null; // Sistema — sem usuário humano
                $ap->data_aprovacao   = now();
                $salvou = $ap->save();

                // LOG 3: Confirmação estrita do save() no banco
                if ($salvou) {
                    $this->line("<info>-> [APROVADO]</info> Apontamento #{$ap->id} ({$colaborador->nome_completo}) atualizado no banco de dados.");
                    Log::info("Aprovação automática concluída para apontamento #{$ap->id}.");
                    
                    AuditoriaService::registrarSistema(
                        'APROVACAO', 
                        'Apontamento', 
                        $ap->id, 
                        'Aprovação automática pelo sistema (Conformidade CLT)'
                    );
                    $aprovados++;
                } else {
                    $this->error("-> [ERRO] Falha ao tentar salvar o apontamento #{$ap->id} no banco.");
                }
            }
        }

        $this->info("=====================================");
        $this->info("Resumo: {$aprovados} aprovados | {$barrados} retidos por exceção CLT.");
        Log::info("Job timesheet:aprovar-automatico finalizado. Aprovados: {$aprovados}, Barrados: {$barrados}.");
    }
}
