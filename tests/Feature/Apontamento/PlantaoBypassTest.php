<?php

namespace Tests\Feature\Apontamento;

use App\Models\User;
use App\Models\Colaborador;
use App\Models\Feriado;
use App\Http\Controllers\ApontamentoController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PlantaoBypassTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Colaborador $colaborador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->colaborador = Colaborador::create([
            'id_colaborador' => 999,
            'nome_completo' => 'Colaborador Teste',
            'cidade_trabalho' => 'CAMPINAS - SP',
            'cargo' => 'OPERACIONAL',
            'ativo' => true,
            'data_admissao' => '2022-01-01',
        ]);

        $this->user = User::factory()->create([
            'id_usuario_erp' => 999,
            'produtividade_colaborador_id' => $this->colaborador->id
        ]);
    }

    #[Test]
    public function deve_negar_plantao_em_dia_util_fora_do_horario()
    {
        // Terça-feira, 15:00 (fora da janela que começa às 17:00)
        $dataHora = Carbon::parse('2026-06-23 15:00:00'); // 23/06/2026 é Terça
        
        // Simula ERP: na escala de hoje
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'data' => [
                    'tecnicos' => [['id_usuario' => 999]]
                ]
            ], 200)
        ]);

        $controller = app(ApontamentoController::class);
        $elegivel = $controller->verificarElegibilidadePlantao(999, $dataHora);

        $this->assertFalse($elegivel);
    }

    #[Test]
    public function deve_permitir_plantao_em_dia_util_dentro_do_horario()
    {
        // Terça-feira, 18:00 (dentro da janela)
        $dataHora = Carbon::parse('2026-06-23 18:00:00'); 
        
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'data' => [
                    'tecnicos' => [['id_usuario' => 999]]
                ]
            ], 200)
        ]);

        $controller = app(ApontamentoController::class);
        $elegivel = $controller->verificarElegibilidadePlantao(999, $dataHora);

        $this->assertTrue($elegivel);
    }

    #[Test]
    public function deve_permitir_plantao_no_fim_de_semana_em_qualquer_horario()
    {
        // Sábado, 12:00 (fim de semana, deve bypassar a restrição de horário)
        $dataHora = Carbon::parse('2026-06-27 12:00:00'); 
        
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'data' => [
                    'tecnicos' => [['id_usuario' => 999]]
                ]
            ], 200)
        ]);

        $controller = app(ApontamentoController::class);
        $elegivel = $controller->verificarElegibilidadePlantao(999, $dataHora);

        $this->assertTrue($elegivel);
    }

    #[Test]
    public function deve_permitir_plantao_no_feriado_em_qualquer_horario()
    {
        // Terça-feira, 10:00 (mas é Feriado em Campinas)
        $dataHora = Carbon::parse('2026-06-23 10:00:00'); 
        
        Feriado::create([
            'descricao' => 'Feriado Municipal',
            'data' => '2026-06-23',
            'cidade' => 'Campinas',
            'uf' => 'SP',
        ]);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'data' => [
                    'tecnicos' => [['id_usuario' => 999]]
                ]
            ], 200)
        ]);

        $controller = app(ApontamentoController::class);
        $elegivel = $controller->verificarElegibilidadePlantao(999, $dataHora);

        $this->assertTrue($elegivel);
    }

    #[Test]
    public function deve_negar_plantao_no_feriado_se_nao_estiver_na_escala()
    {
        // Terça-feira, 10:00 (Feriado em Campinas)
        $dataHora = Carbon::parse('2026-06-23 10:00:00'); 
        
        Feriado::create([
            'descricao' => 'Feriado Municipal',
            'data' => '2026-06-23',
            'cidade' => 'Campinas',
            'uf' => 'SP',
        ]);

        // ERP retorna vazio (não está na escala)
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'data' => []
            ], 200)
        ]);

        $controller = app(ApontamentoController::class);
        $elegivel = $controller->verificarElegibilidadePlantao(999, $dataHora);

        $this->assertFalse($elegivel);
    }
}
