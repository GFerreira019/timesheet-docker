<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncErpUsuarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-usuarios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os usuários e colaboradores vindos do ERP';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\ErpIntegrationService $erpService)
    {
        $this->info("Iniciando sincronização de usuários do ERP...");

        $resultado = $erpService->syncUsuarios();

        if (isset($resultado['success']) && $resultado['success']) {
            $this->info("Sincronização concluída com sucesso!");
            if (isset($resultado['synced'])) {
                $this->info("Usuários sincronizados: " . $resultado['synced']);
            }
        } else {
            $this->error("Erro ao sincronizar: " . ($resultado['message'] ?? 'Desconhecido'));
        }
    }
}
