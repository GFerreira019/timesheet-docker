<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ErpIntegrationService;

class SyncErpObras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-obras';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza as obras da API do ERP para a tabela local';

    /**
     * Execute the console command.
     */
    public function handle(ErpIntegrationService $erpService)
    {
        $this->info('Iniciando sincronização de obras do ERP...');
        
        $result = $erpService->syncObras();
        
        if ($result['success']) {
            $this->info($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
}
