<?php

namespace Tests\Feature\Apontamento;

use App\Models\Apontamento;
use App\Models\CentroCusto;
use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Tests\TestCase;

class CriacaoEdicaoTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $colaborador;
    protected $centroCusto;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::create([
            'name' => 'Teste',
            'email' => 'teste@teste.com',
            'id_usuario_erp' => 999
        ]);
        
        $this->colaborador = Colaborador::create([
            'nome_completo' => 'Colaborador Teste',
            'cargo' => 'OPERACIONAL',
            'ativo' => true,
            'id_colaborador' => 999
        ]);
        
        $this->user->produtividade_colaborador_id = $this->colaborador->id;
        $this->user->save();

        $this->centroCusto = CentroCusto::create([
            'nome' => 'Centro Teste CLT',
            'codigo_erp' => 'CC-TESTE-01',
        ]);
    }

    public function testa_criacao_manual_simples()
    {
        $this->actingAs($this->user);

        $dados = [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => now()->subDay()->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_termino' => '12:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ];

        $response = $this->postJson(route('apontamentos.store'), $dados);

        $response->assertStatus(201);
        $this->assertDatabaseHas('apontamentos', [
            'colaborador_id' => $this->colaborador->id,
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'hora_inicio' => '08:00:00',
            'hora_termino' => '12:00:00',
        ]);
    }

    public function testa_validacao_viagem_no_tempo_datas_futuras()
    {
        $this->actingAs($this->user);
        $amanha = now()->addDay()->format('Y-m-d');

        $dados = [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => $amanha,
            'hora_inicio' => '08:00',
            'hora_termino' => '12:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ];

        $response = $this->postJson(route('apontamentos.store'), $dados);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['hora_inicio', 'hora_termino']);
    }

    public function testa_validacao_inversao_de_horas()
    {
        $this->actingAs($this->user);

        $dados = [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => now()->format('Y-m-d'),
            'hora_inicio' => '16:00',
            'hora_termino' => '14:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ];

        $response = $this->postJson(route('apontamentos.store'), $dados);

        $response->assertStatus(422);
        // Pode ser 'hora_termino' ou 'hora_inicio' ou '__conflito__' dependendo da mensagem de inversão
        $response->assertJsonValidationErrors(['hora_termino']);
    }

    public function testa_modo_checkin_start()
    {
        $this->actingAs($this->user);

        $dados = [
            'tipo_acao' => 'START',
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => now()->format('Y-m-d'),
            'hora_inicio' => now()->format('H:i'), // Simula o horário exato
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ];

        $response = $this->post(route('apontamentos.store'), $dados);

        // Como é requisição sem ser expectsJson, no START faz redirect
        $response->assertRedirect(route('apontamentos.create'));

        $ap = Apontamento::first();
        $this->assertNotNull($ap);
        $this->assertNull($ap->hora_termino);
        $this->assertEquals('EM_ANALISE', $ap->status_aprovacao);
    }

    public function testa_modo_checkout_stop()
    {
        $this->actingAs($this->user);

        $ap = Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'registrado_por_id' => $this->user->id,
            'data_apontamento' => now()->format('Y-m-d'),
            'hora_inicio' => now()->subHours(2)->format('H:i:s'),
            'hora_termino' => null, // Em andamento
        ]);

        // Rota correta para STOP é api.timer.stop
        $response = $this->postJson(route('api.timer.stop'));
        
        $ap->refresh();
        if (is_null($ap->hora_termino)) {
            dump($response->getContent());
        }
        $this->assertNotNull($ap->hora_termino);
        $this->assertNotNull($ap->duracao_total_str);
    }

    public function testa_fluxo_aprovacao_gestor()
    {
        // 1. Criar apontamento com status_ajuste 'PENDENTE'
        $ap = Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'registrado_por_id' => $this->user->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '08:00',
            'hora_termino' => '12:00',
            'local_execucao' => 'INTERNO',
            'status_aprovacao' => 'SOLICITACAO_AJUSTE',
            'status_ajuste' => 'PENDENTE',
            'motivo_ajuste' => 'Esqueci de bater o ponto'
        ]);

        // 2. Criar um gestor/owner
        $gestor = User::create([
            'name' => 'Gestor Teste',
            'email' => 'gestor@teste.com'
        ]);
        $gestorColab = Colaborador::create([
            'nome_completo' => 'Colaborador Gestor',
            'cargo' => 'GERENCIAL',
            'ativo' => true,
            'id_colaborador' => 998,
        ]);
        $gestor->produtividade_colaborador_id = $gestorColab->id;
        $gestor->save();
        
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'COORDENADOR']);
        $gestor->assignRole('COORDENADOR');
        
        $this->actingAs($gestor);

        // 3. Aprovar o ajuste
        $response = $this->post(route('apontamentos.aprovar_ajuste', $ap->id));

        $response->assertRedirect();
        
        $ap->refresh();
        $this->assertEquals('APROVADO', $ap->status_ajuste);
    }
}
