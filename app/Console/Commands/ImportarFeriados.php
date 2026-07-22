<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Feriado;
use Carbon\Carbon;

class ImportarFeriados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timesheet:importar-feriados {ano? : O ano para importar (padrão é o ano atual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa os feriados nacionais de uma API pública para o banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ano = $this->argument('ano') ?? Carbon::now()->year;
        
        $this->info("Buscando feriados nacionais para o ano de {$ano}...");

        $response = Http::get("https://brasilapi.com.br/api/feriados/v1/{$ano}");

        if ($response->failed()) {
            $this->error("Falha ao se comunicar com a BrasilAPI. Status: {$response->status()}");
            return Command::FAILURE;
        }

        $feriados = $response->json();
        $cadastrados = 0;

        foreach ($feriados as $feriadoApi) {
            // Ignorar feriados facultativos se houver ou processar conforme regra.
            // A BrasilAPI retorna 'name', 'date', 'type' (national, etc).
            
            $feriadoModel = Feriado::updateOrCreate(
                [
                    'data' => $feriadoApi['date'],
                    'cidade' => null, // Nacional
                    'uf' => null,
                ],
                [
                    'descricao' => $feriadoApi['name']
                ]
            );

            if ($feriadoModel->wasRecentlyCreated || $feriadoModel->wasChanged()) {
                $cadastrados++;
            }
        }

        $this->info("Importação concluída. {$cadastrados} feriados processados com sucesso.");
        
        return Command::SUCCESS;
    }
}
