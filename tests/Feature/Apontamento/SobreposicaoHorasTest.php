<?php

namespace Tests\Feature\Apontamento;

use App\Models\Apontamento;
use App\Models\CentroCusto;
use App\Models\Colaborador;
use App\Models\User;
use App\Models\SolidesPonto;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SobreposicaoHorasTest extends TestCase
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
            'name' => 'Teste',
            'email' => 'teste@teste.com',
            'solides_id' => 12345,
        ]);
        
        $this->colaborador = Colaborador::create([
            'nome_completo' => 'Colaborador Teste',
            'user_id' => $this->user->id,
            'cargo' => 'OPERACIONAL',
            'solides_id' => 12345,
            'ativo' => true,
            'id_colaborador' => 999, // In case it's needed by ConformidadeCLTService
        ]);

        $this->user->update(['produtividade_colaborador_id' => $this->colaborador->id]);

        $this->centroCusto = CentroCusto::create([
            'nome' => 'Centro Teste',
            'permite_alocacao' => false,
            'ativo' => true,
        ]);
    }

    #[Test]
    public function bloqueia_sobreposicao_no_mesmo_dia()
    {
        Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '08:00:00',
            'hora_termino' => '12:00:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'status_aprovacao' => 'APROVADO',
            'registrado_por_id' => $this->user->id,
            'contagem_edicao' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '10:00',
            'hora_termino' => '14:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertSessionHasErrors(['hora_inicio', 'hora_termino']);
        $this->assertEquals(1, Apontamento::count()); 
    }

    #[Test]
    public function bloqueia_conflito_de_interjornada_overnight()
    {
        Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '22:00:00',
            'hora_termino' => '02:00:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'status_aprovacao' => 'APROVADO',
            'registrado_por_id' => $this->user->id,
            'contagem_edicao' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-11',
            'hora_inicio' => '01:00',
            'hora_termino' => '05:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertSessionHasErrors(['hora_inicio', 'hora_termino']);
        $this->assertEquals(1, Apontamento::count());
    }

    #[Test]
    public function bloqueia_conflito_com_intervalo_da_solides()
    {
        SolidesPonto::create([
            'solides_ponto_id' => 101,
            'colaborador_id' => $this->colaborador->id,
            'data' => '2023-10-12',
            'hora_entrada' => '08:00:00',
            'hora_saida' => '12:00:00',
        ]);
        
        SolidesPonto::create([
            'solides_ponto_id' => 102,
            'colaborador_id' => $this->colaborador->id,
            'data' => '2023-10-12',
            'hora_entrada' => '13:00:00',
            'hora_saida' => '17:00:00',
        ]);

        $batidas = SolidesPonto::whereDate('data', '2023-10-12')->get();
        $this->assertCount(2, $batidas, "Falha: Batidas nao foram salvas ou query whereDate nao funciona no SQLite.");

        $response = $this->actingAs($this->user)->post(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-12',
            'hora_inicio' => '12:30',
            'hora_termino' => '13:30',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertSessionHasErrors(['hora_inicio']);
        $this->assertEquals(0, Apontamento::count());
    }

    #[Test]
    public function permite_edicao_do_proprio_registro_sem_conflito()
    {
        $ap = Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '08:00:00',
            'hora_termino' => '12:00:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'status_aprovacao' => 'EM_ANALISE',
            'registrado_por_id' => $this->user->id,
            'contagem_edicao' => 0,
        ]);

        $response = $this->actingAs($this->user)->put(route('apontamentos.update', $ap->id), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '09:00',
            'hora_termino' => '12:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('09:00', date('H:i', strtotime($ap->fresh()->hora_inicio)));
    }

    #[Test]
    public function bloqueia_novo_apontamento_se_check_in_ativo()
    {
        Apontamento::create([
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '08:00:00',
            'hora_termino' => null,
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
            'status_aprovacao' => 'EM_ANALISE',
            'registrado_por_id' => $this->user->id,
            'contagem_edicao' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('apontamentos.store'), [
            'colaborador_id' => $this->colaborador->id,
            'data_apontamento' => '2023-10-10',
            'hora_inicio' => '14:00',
            'hora_termino' => '18:00',
            'local_execucao' => 'INTERNO',
            'centro_custo_id' => $this->centroCusto->id,
        ]);

        $response->assertSessionHas('error', 'Você possui uma atividade em andamento (Check-in). Finalize-a antes de iniciar outra.');
        $this->assertEquals(1, Apontamento::count()); 
    }
}
