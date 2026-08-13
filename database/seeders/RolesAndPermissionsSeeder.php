<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RolesAndPermissionsSeeder — Cria os grupos/roles do sistema.
 *
 * Níveis de Acesso oficiais definidos:
 *   1. ADMIN
 *   2. GERENCIAL
 *   3. SAC
 *   4. COORDENADOR
 *   5. OPERACIONAL
 *
 * Rodar com: php artisan db:seed --class=RolesAndPermissionsSeeder
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa o cache do Spatie antes de criar/verificar roles.
        // Essencial para re-seeds em banco existente: evita que roles cacheadas
        // causem erros silenciosos no assignRole() logo em seguida.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['ADMIN', 'GERENCIAL', 'SAC', 'COORDENADOR', 'OPERACIONAL'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role, 'guard_name' => 'web']
            );
            $this->command->info("Role criada/verificada: {$role}");
        }

        $this->command->newLine();
        $this->command->info('✅ Roles do sistema (ADMIN, GERENCIAL, SAC, COORDENADOR, OPERACIONAL) configuradas com sucesso.');
    }
}
