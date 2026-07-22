<?php

namespace Database\Seeders;

use App\Helpers\AcessoHelper;
use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * DatabaseSeeder — Popula o banco com dados iniciais para desenvolvimento.
 *
 * Cria:
 * 1. Roles do sistema (GESTOR, ADMINISTRATIVO, COORDENADOR)
 * 2. Usuário Owner (admin@sistema.com / password) — is_superuser = true
 * 3. Colaborador vinculado ao Owner
 *
 * Rodar com: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Roles
        $this->call(RolesSeeder::class);

        // PERFIL 1: OWNER / SUPERUSER
        $owner = User::firstOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name'         => 'Administrador',
                'password'     => Hash::make('password'),
                'is_superuser' => true,
            ]
        );

        Colaborador::firstOrCreate(
            ['user_id' => $owner->id],
            [
                'id_colaborador' => 'ADM001',
                'nome_completo'  => 'Administrador do Sistema',
                'cargo'          => 'DIRETOR',
                'cidade'         => 'São Paulo',
                'uf'             => 'SP',
            ]
        );

        // PERFIL 2: LÍDER / GESTOR (Aprovações)
        $gestor = User::firstOrCreate(
            ['email' => 'lider@sistema.com'],
            [
                'name'         => 'Líder de Obra',
                'password'     => Hash::make('password'),
                'is_superuser' => false,
            ]
        );
        $gestor->assignRole('GESTOR'); 

        Colaborador::firstOrCreate(
            ['user_id' => $gestor->id],
            [
                'id_colaborador' => 'LID001',
                'nome_completo'  => 'Líder / Encarregado',
                'cargo'          => 'ENCARREGADO',
                'cidade'         => 'Campinas',
                'uf'             => 'SP',
            ]
        );

        // PERFIL 3: OPERADOR BASE
        $operador = User::firstOrCreate(
            ['email' => 'operador@sistema.com'],
            [
                'name'         => 'Operador Padrão',
                'password'     => Hash::make('password'),
                'is_superuser' => false,
            ]
        );

        Colaborador::firstOrCreate(
            ['user_id' => $operador->id],
            [
                'id_colaborador' => 'OPE001',
                'nome_completo'  => 'Operador da Silva',
                'cargo'          => 'OPERADOR DE MAQUINAS',
                'cidade'         => 'Campinas',
                'uf'             => 'SP',
            ]
        );

        $this->command->newLine();
        $this->command->info('✅ Seeder concluído!');
        $this->command->table(
            ['Tipo', 'Email', 'Senha', 'Acesso Mágico'],
            [
                ['Owner (Superuser)', 'admin@sistema.com', 'password', 'http://localhost:8000/dev/painel'],
                ['Gestor (Aprovador)', 'lider@sistema.com', 'password', 'http://localhost:8000/dev/painel'],
                ['Operador (Apontador)', 'operador@sistema.com', 'password', 'http://localhost:8000/dev/painel'],
            ]
        );
        $this->command->warn('⚠️  Altere a senha do Owner após o primeiro login!');
    }
}
