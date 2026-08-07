<?php

namespace Database\Seeders;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DatabaseSeeder — Popula o banco com dados iniciais para desenvolvimento.
 *
 * Cria:
 * 1. Roles do sistema (via RolesSeeder)
 * 2. Usuário Owner (admin@sistema.com) — role ADMIN
 * 3. Usuário Gestor (lider@sistema.com) — role COORDENADOR
 * 4. Usuário Operador (operador@sistema.com) — role OPERACIONAL
 *
 * ARQUITETURA FK:
 *   produtividade_colaborador é criado ANTES do users.
 *   users.produtividade_colaborador_id → produtividade_colaborador.id (PK interna)
 *   Esse padrão espelha o SyncErpUserService e satisfaz a FK do PostgreSQL.
 *
 * Rodar com: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Roles (limpa cache Spatie internamente)
        $this->call(RolesSeeder::class);

        // ---------------------------------------------------------------
        // PERFIL 1: OWNER / SUPERUSER
        // Passo 1: cria o colaborador ANTES do User (satisfaz FK do Postgres)
        // ---------------------------------------------------------------
        $colaboradorOwner = Colaborador::firstOrCreate(
            ['id_colaborador' => 'ADM001'],
            [
                'nome_completo'  => 'Administrador do Sistema',
                'nivel_acesso'   => 'OPERACIONAL',
                'cargo'          => 'DIRETOR',
                'cidade_moradia' => 'São Paulo',
                'uf'             => 'SP',
            ]
        );

        // Passo 2: cria o User apontando para a PK interna do colaborador
        $owner = User::updateOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name'                         => 'Administrador',
                'produtividade_colaborador_id' => $colaboradorOwner->id,
            ]
        );
        // O papel de maior acesso cadastrado é ADMIN
        $owner->assignRole('ADMIN');

        // ---------------------------------------------------------------
        // PERFIL 2: LÍDER / GESTOR (Aprovações)
        // ---------------------------------------------------------------
        $colaboradorGestor = Colaborador::firstOrCreate(
            ['id_colaborador' => 'LID001'],
            [
                'nome_completo'  => 'Líder / Encarregado',
                'nivel_acesso'   => 'GESTOR',
                'cargo'          => 'ENCARREGADO',
                'cidade_moradia' => 'Campinas',
                'uf'             => 'SP',
            ]
        );

        $gestor = User::updateOrCreate(
            ['email' => 'lider@sistema.com'],
            [
                'name'                         => 'Líder de Obra',
                'produtividade_colaborador_id' => $colaboradorGestor->id,
            ]
        );
        $gestor->assignRole('COORDENADOR');

        // ---------------------------------------------------------------
        // PERFIL 3: OPERADOR BASE
        // ---------------------------------------------------------------
        $colaboradorOperador = Colaborador::firstOrCreate(
            ['id_colaborador' => 'OPE001'],
            [
                'nome_completo'  => 'Operador da Silva',
                'nivel_acesso'   => 'OPERACIONAL',
                'cargo'          => 'OPERADOR DE MAQUINAS',
                'cidade_moradia' => 'Campinas',
                'uf'             => 'SP',
            ]
        );

        $operador = User::updateOrCreate(
            ['email' => 'operador@sistema.com'],
            [
                'name'                         => 'Operador Padrão',
                'produtividade_colaborador_id' => $colaboradorOperador->id,
            ]
        );
        $operador->assignRole('OPERACIONAL');

        $this->command->newLine();
        $this->command->info('✅ Seeder concluído!');
        $this->command->table(
            ['Tipo', 'Email', 'Acesso Mágico'],
            [
                ['Owner (Admin)',        'admin@sistema.com',    'http://localhost:8000/dev/painel'],
                ['Gestor (Aprovador)',   'lider@sistema.com',    'http://localhost:8000/dev/painel'],
                ['Operador (Apontador)', 'operador@sistema.com', 'http://localhost:8000/dev/painel'],
            ]
        );
        $this->command->warn('⚠️  Autenticação gerida pelo ERP. As senhas locais foram desativadas!');
    }
}
