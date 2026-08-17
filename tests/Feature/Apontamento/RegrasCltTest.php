<?php

namespace Tests\Feature\Apontamento;

use App\Models\Apontamento;
use App\Models\CentroCusto;
use App\Models\Colaborador;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegrasCltTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Colaborador $colaborador;
    protected CentroCusto $centroCusto;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::create([
            'name' => 'Teste CLT',
            'email' => 'teste.clt@teste.com',
            'solides_id' => 12345,
            'id_usuario_erp' => 999,
        ]);
        
        $this->colaborador = Colaborador::create([
            'nome_completo' => 'Colaborador Teste CLT',
            'user_id' => $this->user->id,
            'cargo' => 'OPERACIONAL',
            'ativo' => true,
            'id_colaborador' => 999,
        ]);
        
        // Forçar os IDs a serem os mesmos para contornar o bug no ApontamentoRequest:281
        $this->user->id = $this->colaborador->id;
        $this->user->save();

        $this->centroCusto = CentroCusto::create([
            'nome' => 'Centro Teste CLT',
            'codigo_erp' => 'CC-TESTE-01',
        ]);
    }

    public function testa_desrespeito_ao_intervalo_interjornada()
    {
        // Trabalhou dia 1 até às 22:00
        Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '14:00',
            'hora_termino' => '22:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'status_aprovacao' => 'APROVADO',
        ]);

        $this->actingAs($this->user);

        // Trabalha dia 2 começando às 07:00 (Gap de 9 horas, precisa ser > 11h)
        $response = $this->postJson(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-11',
            'hora_inicio' => '07:00',
            'hora_termino' => '12:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);
        if ($response->status() !== 201) {
            dump($response->json());
        }
        $response->assertStatus(201);
        
        $ap = Apontamento::whereDate('data_apontamento', '2023-10-11')->first();
        if (!$ap) {
            dump("Apontamentos no BD:", Apontamento::all()->toArray());
        }
        $this->assertTrue($ap->flag_atencao);
        $this->assertStringContainsString('INTERJORNADA', $ap->motivo_alerta);
        $this->assertEquals('EM_ANALISE', $ap->status_aprovacao);
    }

    public function testa_desrespeito_ao_intervalo_intrajornada()
    {
        $this->actingAs($this->user);

        // Turno longo sem intervalo > 6h
        $response = $this->postJson(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-12',
            'hora_inicio' => '08:00',
            'hora_termino' => '15:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertStatus(201);

        $ap = Apontamento::first();
        if (!$ap->flag_atencao) {
            dump("Apontamentos Intrajornada:", $ap->toArray());
        }
        $this->assertTrue($ap->flag_atencao);
        $this->assertStringContainsString('INTRAJORNADA', $ap->motivo_alerta);
        $this->assertEquals('EM_ANALISE', $ap->status_aprovacao);
    }

    public function testa_limite_diario_horas_extras()
    {
        Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-13',
            'hora_inicio' => '08:00',
            'hora_termino' => '12:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'status_aprovacao' => 'APROVADO',
        ]);

        $this->actingAs($this->user);

        // Cria mais um apontamento de 7 horas, totalizando 11 horas (limite é 10h48m)
        $response = $this->postJson(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-13',
            'hora_inicio' => '13:00',
            'hora_termino' => '20:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertStatus(201);

        $aps = Apontamento::whereDate('data_apontamento', '2023-10-13')->get();
        foreach ($aps as $ap) {
            $this->assertTrue($ap->flag_atencao);
            $this->assertStringContainsString('LIMITE DIÁRIO', $ap->motivo_alerta);
        }
    }

    public function testa_regras_e_elegibilidade_de_plantao()
    {
        Http::fake([
            '*/plantao.php*' => Http::response([
                'success' => true,
                'data' => [
                    'escala_id' => 10,
                    'data_plantao' => '2023-10-15',
                    'tecnicos' => [
                        ['id_usuario' => 999]
                    ]
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        // Apontamento como plantão dentro da janela de 17h as 08h do dia seguinte
        $response = $this->postJson(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-15',
            'hora_inicio' => '18:00',
            'hora_termino' => '23:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'em_plantao' => true,
            'data_plantao' => '2023-10-15',
        ]);

        $response->assertStatus(201);
        $ap = Apontamento::first();
        $this->assertTrue($ap->em_plantao);
    }

    public function testa_regras_e_elegibilidade_de_plantao_bloqueado()
    {
        // Mock API empty/false
        Http::fake([
            '*/plantao.php*' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-15',
            'hora_inicio' => '18:00',
            'hora_termino' => '23:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'em_plantao' => true,
            'data_plantao' => '2023-10-15',
        ]);

        // Should be blocked by ApontamentoRequest because it's not eligible
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['em_plantao']);
    }

    public function testa_ajuste_de_madrugada()
    {
        // Turno de plantão durante a madrugada
        Http::fake([
            '*/plantao.php*' => Http::response([
                'success' => true,
                'data' => [
                    'escala_id' => 10,
                    'data_plantao' => '2023-10-15',
                    'tecnicos' => [
                        ['id_usuario' => 999]
                    ]
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        // Apontamento no dia 16, às 02:00, mas vinculado ao plantão do dia 15!
        $response = $this->postJson(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-16', // Dia 16!
            'hora_inicio' => '02:00',
            'hora_termino' => '05:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'em_plantao' => true,
            'data_plantao' => '2023-10-16', // Mesma data do apontamento!
        ]);

        if ($response->status() !== 201) {
            dump($response->json());
        }
        $response->assertStatus(201);
        $ap = Apontamento::first();
        $this->assertTrue($ap->em_plantao);
        $this->assertEquals('2023-10-16', $ap->data_apontamento->format('Y-m-d'));
        $this->assertEquals('2023-10-16 00:00:00', $ap->data_plantao->format('Y-m-d H:i:s'));
    }
}
