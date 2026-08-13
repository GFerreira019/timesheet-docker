<?php

namespace Database\Seeders;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DatabaseSeeder — Popula o banco com dados iniciais para desenvolvimento/produção.
 *
 * Cria:
 * 1. Roles do sistema (via RolesAndPermissionsSeeder)
 * 2. Usuário Super Admin de Resgate (email e senha via .env) — role ADMIN
 *
 * Os demais usuários (Gerentes, Operacionais, etc.) virão da integração com o ERP.
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
        // PORTA DOS FUNDOS: SUPER ADMIN DE RESGATE (SSO FALBACK)
        // ---------------------------------------------------------------
        $colaboradorSuperAdmin = Colaborador::firstOrCreate(
            ['id_colaborador' => 'RESGATE001'],
            [
                'nome_completo'  => 'Super Admin (Resgate)',
                'nivel_acesso'   => 'ADMIN',
                'cargo'          => 'SUPORTE TI',
                'cidade_moradia' => 'Remoto',
                'uf'             => 'SP',
            ]
        );

        $superAdmin = User::updateOrCreate(
            ['email' => env('ADMIN_DEFAULT_EMAIL', 'suporte@timesheet.com')],
            [
                'name'                         => 'Super Admin Resgate',
                'password'                     => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'SenhaSegura123!')),
                'produtividade_colaborador_id' => $colaboradorSuperAdmin->id,
            ]
        );
        $superAdmin->assignRole('ADMIN');

        $this->command->newLine();
        $this->command->info('✅ Seeder concluído! Roles criadas e Super Admin gerado.');
        $this->command->table(
            ['Tipo', 'Email', 'Role', 'Acesso Mágico'],
            [
                ['Super Admin (Resgate)', env('ADMIN_DEFAULT_EMAIL', 'suporte@timesheet.com'), 'ADMIN', 'http://localhost:8000/dev/painel'],
            ]
        );
        $this->command->warn('⚠️  Demais usuários serão geridos pelo ERP via integração/SSO.');
    }
}
