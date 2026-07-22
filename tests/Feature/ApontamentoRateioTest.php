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
        $user = User::factory()->create(['is_superuser' => true]);

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

        // Setup: Projetos do ERP inseridos manualmente
        $projeto1 = Projeto::create(['codigo' => 'OBR001', 'nome' => 'Edifício Alpha', 'ativo' => true]);
        $projeto2 = Projeto::create(['codigo' => 'OBR002', 'nome' => 'Residencial Beta', 'ativo' => true]);
        $projeto3 = Projeto::create(['codigo' => 'OBR003', 'nome' => 'Condomínio Gama', 'ativo' => true]);

        $dados = [
            'colaborador_id' => $colaborador->id,
            'data_apontamento' => '2026-06-25',
            'local_execucao' => 'INT',
            'projeto_id' => $projeto1->id,
            'hora_inicio' => '08:00',
            'hora_termino' => '17:00',
            'registrar_multiplas_obras' => true,
            'obras_extras_list' => json_encode([$projeto1->id, $projeto2->id, $projeto3->id]),
            'centro_custo_id' => null,
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
