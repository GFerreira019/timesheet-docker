<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Colaborador;
use App\Models\Apontamento;
use App\Models\Projeto;

class ApontamentoRateioTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_criar_apontamentos_rateados_para_multiplas_obras()
    {
        // Setup: Usuário nativo com privilégios de rateio
        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'COORDENADOR', 'guard_name' => 'web']);
        $user->assignRole($role);

        // Setup: Colaborador inserido manualmente (sem factory)
        $colaborador = Colaborador::create([
            'id_colaborador' => 9999,
            'user_id' => $user->id,
            'nome_completo' => 'João Silva (Teste)',
            'cpf' => '00000000000',
            'cargo' => 'Operador',
            'data_admissao' => '2023-01-01',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'telefone' => '11999999999',
            'is_ativo' => true,
        ]);

        $this->actingAs($user);

        // Setup: Cliente Operacional inserido manualmente
        $cliente = \App\Models\ClienteOperacional::create(['codigo' => 'CLI001', 'nome' => 'Cliente Teste']);

        // Setup: Projetos do ERP inseridos manualmente na nova estrutura
        $projeto1 = \App\Models\ProjetoOperacional::create(['cliente_operacional_id' => $cliente->id, 'codigo' => 'OBR001', 'unidade' => 'A', 'ativo' => true]);
        $projeto2 = \App\Models\ProjetoOperacional::create(['cliente_operacional_id' => $cliente->id, 'codigo' => 'OBR002', 'unidade' => 'A', 'ativo' => true]);
        $projeto3 = \App\Models\ProjetoOperacional::create(['cliente_operacional_id' => $cliente->id, 'codigo' => 'OBR003', 'unidade' => 'A', 'ativo' => true]);
        $centroCusto = \App\Models\CentroCusto::create(['codigo' => 'CC001', 'nome' => 'Administrativo', 'ativo' => true, 'permite_alocacao' => true]);

        $dados = [
            'colaborador_id' => $colaborador->id,
            'data_apontamento' => '2026-06-25',
            'local_execucao' => 'INTERNO',
            'projeto_id' => $projeto1->id,
            'unidade' => 'A',
            'hora_inicio' => '08:00',
            'hora_termino' => '17:00',
            'registrar_multiplas_obras' => true,
            'rateio' => [
                ['tipo' => 'P', 'codigo' => $projeto2->codigo, 'unidade' => 'A'],
                ['tipo' => 'P', 'codigo' => $projeto3->codigo, 'unidade' => 'A'],
            ],
            'obras_extras_list' => [$projeto2->id, $projeto3->id], // fallback legacy
            'centro_custo_id' => $centroCusto->id,
            'descricao' => 'Trabalho rateado nas 3 obras',
            'acao' => 'STOP',
        ];

        // Action: Post para a rota de criação
        $response = $this->post(route('apontamentos.store'), $dados);

        // Assert: Deve redirecionar com sucesso (se houver erro, retornaria um redirect de erro 302 sem a rota final ou 500)
        $response->assertStatus(302);
        
        $response->dumpSession();

        // Assert: Devem ter sido criados 3 apontamentos separados no banco para essa data
        $this->assertDatabaseCount('apontamentos', 3);
        
        // As durações devem ser divididas. 08:00 as 17:00 = 9h = 540 minutos.
        // 540 / 3 = 180 minutos (3 horas por obra) = 10800 segundos.
        $apontamentos = Apontamento::all();
        
        foreach ($apontamentos as $ap) {
            $this->assertContains($ap->projeto_id, [$projeto1->id, $projeto2->id, $projeto3->id]);
            $this->assertEquals('EM_ANALISE', $ap->status_aprovacao);
            $this->assertEquals(10800, $ap->duracao_em_segundos); 
        }
    }
}
