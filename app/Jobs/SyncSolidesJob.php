<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Colaborador;
use App\Services\SolidesService;
use Illuminate\Support\Facades\Cache;

class SyncSolidesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $syncId;
    public $dataInicio;
    public $dataFim;

    public function __construct($syncId, $dataInicio, $dataFim)
    {
        $this->syncId = $syncId;
        $this->dataInicio = $dataInicio;
        $this->dataFim = $dataFim;
    }

    public function handle()
    {
        Cache::put("sync_progress_{$this->syncId}", ['porcentagem' => 0, 'status' => 'processando'], 120);

        $colaboradores = Colaborador::whereNull('data_demissao')
            ->whereHas('setorRelacionamento', function ($query) {
                $query->where('ativo', true);
            })
            ->whereHas('user', function($q) {
                $q->whereNotNull('solides_id');
            })
            ->get();

        $total = $colaboradores->count();
        $atual = 0;

        foreach ($colaboradores as $colab) {
            $atual++;
            $porcentagem = ($total > 0) ? round(($atual / $total) * 100) : 100;

            $sincronizadoRecentemente = \App\Models\SolidesPonto::where('colaborador_id', $colab->id)
                ->whereBetween('data', [$this->dataInicio, $this->dataFim])
                ->where('updated_at', '>=', now()->subMinutes(10))
                ->exists();

            if ($sincronizadoRecentemente) {
                \Illuminate\Support\Facades\Log::info("Pulando {$colab->nome_completo}: Pontos já sincronizados há menos de 10 minutos.");
                
                Cache::put("sync_progress_{$this->syncId}", [
                    'porcentagem' => $porcentagem,
                    'status' => 'processando'
                ], 3600);
                
                continue;
            }

            try {
                SolidesService::buscarEspelhoPonto($colab->id, $this->dataInicio, $this->dataFim);
            } catch (\Exception $e) {
                report($e);
            }

            Cache::put("sync_progress_{$this->syncId}", [
                'porcentagem' => $porcentagem,
                'status' => 'processando'
            ], 3600);

            // Respeitar Rate Limit da Sólides
            sleep(1);
        }

        Cache::put("sync_progress_{$this->syncId}", [
            'porcentagem' => 100,
            'status' => 'concluido'
        ], 3600);
    }
}
