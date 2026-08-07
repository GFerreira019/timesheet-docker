<?php

namespace Database\Seeders;

use App\Models\Colaborador;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * SyncNivelAcessoParaRolesSeeder
 *
 * Fase 1 do plano de unificação AuthZ:
 * Garante que todo usuário com nivel_acesso preenchido no Colaborador
 * também tenha a role equivalente no Spatie/laravel-permission.
 *
 * Mapeamento direto (os nomes são idênticos no sistema):
 *   OPERACIONAL → role 'OPERACIONAL'
 *   GESTOR/COORDENADOR → role 'COORDENADOR'
 *   ADMIN       → role 'ADMIN'
 *   GERENCIAL   → role 'GERENCIAL'
 *   SAC         → role 'SAC'
 *
 * Uso:
 *   php artisan db:seed --class=SyncNivelAcessoParaRolesSeeder
 */
class SyncNivelAcessoParaRolesSeeder extends Seeder
{
    /**
     * Os níveis de acesso válidos do sistema.
     */
    private const NIVEIS_VALIDOS = [
        'OPERACIONAL',
        'COORDENADOR',
        'ADMIN',
        'GERENCIAL',
        'SAC',
    ];

    public function run(): void
    {
        // 1. Garante que todas as roles necessárias existam no Spatie
        foreach (self::NIVEIS_VALIDOS as $nivel) {
            Role::firstOrCreate(
                ['name' => $nivel, 'guard_name' => 'web']
            );
        }

        $this->command->info('Roles criadas/verificadas: ' . implode(', ', self::NIVEIS_VALIDOS));

        // 2. Busca todos os colaboradores com usuario vinculado e nivel_acesso preenchido
        // user_id foi removido de produtividade_colaborador (FK invertida).
        // Usamos whereHas('user') para garantir que o Colaborador tenha um User vinculado
        // via users.produtividade_colaborador_id.
        $colaboradores = Colaborador::whereNotNull('nivel_acesso')
            ->whereHas('user')
            ->with('user')
            ->get();

        $sincronizados = 0;
        $ignorados     = 0;
        $erros         = 0;

        foreach ($colaboradores as $colaborador) {
            $user  = $colaborador->user;
            $nivel = strtoupper(trim($colaborador->nivel_acesso ?? ''));
            
            // Mapeamento de legado: quem era GESTOR agora é COORDENADOR
            if ($nivel === 'GESTOR') {
                $nivel = 'COORDENADOR';
            }

            if (!$user) {
                $this->command->warn("Colaborador ID {$colaborador->id} ({$colaborador->nome_completo}) sem User vinculado. Ignorado.");
                $ignorados++;
                continue;
            }

            if (!in_array($nivel, self::NIVEIS_VALIDOS)) {
                $this->command->warn("Colaborador ID {$colaborador->id}: nivel_acesso '{$nivel}' invalido. Ignorado.");
                $ignorados++;
                continue;
            }

            try {
                // syncRoles remove roles antigas e aplica apenas a nova
                // Garante que o usuário tenha apenas 1 role (equivalente ao nivel_acesso único)
                $user->syncRoles([$nivel]);
                $sincronizados++;
            } catch (\Throwable $e) {
                $this->command->error("Erro ao sincronizar User ID {$user->id}: {$e->getMessage()}");
                $erros++;
            }
        }

        $this->command->newLine();
        $this->command->info("Resultado da sincronizacao:");
        $this->command->table(
            ['Status', 'Quantidade'],
            [
                ['Sincronizados', $sincronizados],
                ['Ignorados',     $ignorados],
                ['Erros',         $erros],
            ]
        );
    }
}
