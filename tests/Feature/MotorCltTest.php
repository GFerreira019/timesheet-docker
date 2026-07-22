<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Colaborador;
use App\Models\Apontamento;
use App\Services\ConformidadeCLTService;
use Carbon\Carbon;

class MotorCltTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_identificar_infracao_de_interjornada_inferior_a_11_horas()
    {
        // Setup: Base
        $user = User::factory()->create();
        $colaborador = Colaborador::create([
            'id_colaborador' => 9998,
            'user_id' => $user->id,
            'nome_completo' => 'Maria Souza (Teste)',
            'cpf' => '11111111111',
            'cargo' => 'Auxiliar',
            'data_admissao' => '2023-01-01',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'telefone' => '11988888888',
            'is_ativo' => true,
        ]);

        // Dia 1: Trabalho pesado até as 23:00 (inserção manual na nova tabela de apontamento)
        $apontamentoDia1 = Apontamento::create([
            'colaborador_id' => $colaborador->id,
            'data_apontamento' => '2026-06-24',
            'projeto_id'     => null,
            'centro_custo_id'=> null,
            'hora_inicio'    => '14:00:00',
            'hora_termino'   => '23:00:00',
            'duracao_segundos' => 9 * 3600,
            'status'         => 'EM_ANALISE',
            'flag_atencao'   => false,
            'modo_checkin'   => false,
        ]);

        // Dia 2: Início às 06:00 (descanso de apenas 7 horas)
        $apontamentoDia2 = Apontamento::create([
            'colaborador_id' => $colaborador->id,
            'data_apontamento' => '2026-06-25',
            'projeto_id'     => null,
            'centro_custo_id'=> null,
            'hora_inicio'    => '08:00:00',
            'hora_termino'   => '17:00:00',
            'duracao_segundos' => 9 * 3600,
            'status'         => 'EM_ANALISE',
            'flag_atencao'   => false,
            'modo_checkin'   => false,
        ]);

        // Action: Aciona o motor CLT simulando o evento pós-save
        $service = new ConformidadeCLTService();
        $service->calcularRegrasClt($colaborador, Carbon::parse('2026-06-25'));

        // Assert: O apontamento do Dia 2 deve ser flaggado devido a interjornada
        $apontamentoDia2->refresh();
        $this->assertTrue((bool) $apontamentoDia2->flag_atencao);
        $this->assertStringContainsString('Descanso mínimo de 11h', $apontamentoDia2->motivo_alerta);
    }
}
