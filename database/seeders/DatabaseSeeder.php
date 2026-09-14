<?php

namespace Database\Seeders;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Popula o banco com dados iniciais para desenvolvimento/produção.
 *
 * Cria:
 * 1. Roles do sistema (via RolesAndPermissionsSeeder)
 * 2. Usuário Super Admin de Resgate (email via .env) — role ADMIN via Spatie
 *
 * Os demais usuários virão da integração com o ERP / SSO.
 *
 * Rodar com: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Roles (limpa cache Spatie internamente)
        $this->call(RolesAndPermissionsSeeder::class);

        // ---------------------------------------------------------------
        // SUPER ADMIN DE RESGATE (SSO FALLBACK)
        // ---------------------------------------------------------------
        $colaboradorSuperAdmin = Colaborador::firstOrCreate(
            ['nome_completo' => 'Super Admin'],
            [
                'cargo'          => 'SUPORTE TI',
                'cidade_moradia' => 'Remoto',
                'uf'             => 'SP',
            ]
        );

        $superAdmin = User::updateOrCreate(
            ['email' => env('ADMIN_DEFAULT_EMAIL', 'suporte@timesheet.com')],
            [
                'name'                         => 'Super Admin',
                'produtividade_colaborador_id' => $colaboradorSuperAdmin->id,
            ]
        );
        $superAdmin->syncRoles(['ADMIN']);

        $this->command->newLine();
        $this->command->info('✅ Seeder concluído! Roles criadas e Super Admin gerado.');
        $this->command->table(
            ['Tipo', 'Email', 'Role', 'Acesso Mágico'],
            [
                ['Super Admin', env('ADMIN_DEFAULT_EMAIL', 'suporte@timesheet.com'), 'ADMIN', url('/dev/painel')],
            ]
        );
        $this->command->warn('⚠️  Demais usuários serão geridos pelo ERP via integração/SSO.');
    }
}
