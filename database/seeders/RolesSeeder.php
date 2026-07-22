<?php

namespace Database\Seeders;

use App\Helpers\AcessoHelper;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * RolesSeeder — Cria os grupos/roles do sistema.
 *
 * Equivalente aos Django Groups criados no Admin:
 *   - GESTOR        → pode aprovar/rejeitar, vê colaboradores do seu setor
 *   - ADMINISTRATIVO → pode lançar para múltiplos colaboradores, fazer rateio
 *   - COORDENADOR   → pode fazer rateio, lança apenas para si mesmo
 *
 * O papel Owner é determinado por users.is_superuser = true (sem role).
 *
 * Rodar com: php artisan db:seed --class=RolesSeeder
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AcessoHelper::GRUPOS as $grupo) {
            Role::firstOrCreate(
                ['name' => $grupo, 'guard_name' => 'web']
            );
            $this->command->info("Role criada/verificada: {$grupo}");
        }

        $this->command->newLine();
        $this->command->info('✅ Roles do sistema configuradas com sucesso.');
        $this->command->line('   Owner é definido por users.is_superuser = true (sem role específica).');
    }
}
