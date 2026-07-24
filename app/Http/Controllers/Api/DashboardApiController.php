<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apontamento;
use App\Models\Colaborador;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DashboardApiController
 *
 * Equivalente às APIs Django:
 *   api_dashboard_data(request)  → data()        GET /api/dashboard
 *   api_exportar_json(request)   → exportarJson() GET /api/exportar-completo
 *   health_check_view(request)   → health()       GET /api/health
 *
 * Autenticação: X-API-KEY header ou usuário autenticado (equivalente ao Django).
 */
class DashboardApiController extends Controller
{
    /**
     * Dados JSON para o Dashboard Externo.
     * Equivalente ao api_dashboard_data() do Django (apis.py L405-469).
     *
     * GET /api/dashboard
     * Autenticação: Header X-API-KEY ou sessão autenticada.
     */
    public function data(Request $request): JsonResponse
    {
        $apiKeyEsperada = config('services.dashboard.api_key');

        if (!$apiKeyEsperada) {
            return response()->json(
                ['erro' => 'Erro de Configuração: API Key não definida no servidor.'],
                500
            );
        }

        $tokenRecebido = $request->header('X-API-KEY');
        if ($tokenRecebido !== $apiKeyEsperada && !auth()->check()) {
            return response()->json(['erro' => 'Acesso Negado'], 403);
        }

        $hoje = now()->toDateString();
        $qs   = Apontamento::with(['projeto', 'colaborador', 'codigoCliente', 'centroCusto'])
            ->where('data_apontamento', $hoje)
            ->get();

        $totalRegistros    = $qs->count();
        $totalSegundos     = 0;
        $projetosAtivos    = [];
        $colaboradoresIds  = [];
        $dummy = '2000-01-01';

        foreach ($qs as $a) {
            if ($a->hora_inicio && $a->hora_termino) {
                $ini = Carbon::parse("{$dummy} {$a->hora_inicio}");
                $fim = Carbon::parse("{$dummy} {$a->hora_termino}");
                if ($fim->lt($ini)) {
                    $fim->addDay();
                }
                $totalSegundos += $ini->diffInSeconds($fim);
            }

            // Label do projeto/setor para o gráfico (equivalente ao Django)
            $nomeProj = 'Outros';
            if ($a->local_execucao === 'EXTERNO') {
                if ($a->projeto) {
                    $nomeProj = $a->projeto->nome;
                } elseif ($a->codigoCliente) {
                    $nomeProj = "Cliente {$a->codigoCliente->codigo}";
                }
            } else {
                if ($a->centroCusto) {
                    $nomeProj = $a->centroCusto->nome;
                }
            }

            $projetosAtivos[$nomeProj] = ($projetosAtivos[$nomeProj] ?? 0) + 1;

            if ($a->colaborador) {
                $colaboradoresIds[$a->colaborador->nome_completo] = true;
            }
        }

        $totalHoras = round($totalSegundos / 3600, 2);

        return response()->json([
            'data_referencia' => now()->format('d/m/Y'),
            'kpis'            => [
                'total_apontamentos'  => $totalRegistros,
                'total_horas'         => $totalHoras,
                'colaboradores_ativos'=> count($colaboradoresIds),
            ],
            'grafico_projetos' => [
                'labels' => array_keys($projetosAtivos),
                'valores'=> array_values($projetosAtivos),
            ],
            'lista_colaboradores' => array_keys($colaboradoresIds),
        ]);
    }

    /**
     * Exporta todos os apontamentos dos últimos N dias em JSON.
     * Equivalente ao api_exportar_json() do Django (apis.py L471-573).
     *
     * GET /api/exportar-completo?days=45
     * Autenticação: Header X-API-KEY obrigatório.
     *
     * Inclui linhas de auxiliares extras (equivalente ao for aux in auxiliares do Django).
     */
    public function exportarJson(Request $request): JsonResponse
    {
        $apiKeyEsperada = config('services.dashboard.api_key');

        if (!$apiKeyEsperada) {
            return response()->json(['erro' => 'Erro de Configuração: API Key não definida no servidor.'], 500);
        }

        $tokenRecebido = $request->header('X-API-KEY');
        if ($tokenRecebido !== $apiKeyEsperada) {
            return response()->json(['erro' => 'Acesso Negado'], 403);
        }

        $days      = (int) ($request->query('days', 45));
        $startDate = now()->subDays($days)->toDateString();

        $queryset = Apontamento::with([
            'projeto',
            'colaborador',
            'veiculo',
            'centroCusto',
            'codigoCliente',
            'auxiliar',
            'auxiliaresExtras',
            'registradoPor',
        ])
        ->where('data_apontamento', '>=', $startDate)
        ->orderBy('data_apontamento')
        ->get();

        $dadosSaida = [];

        foreach ($queryset as $item) {
            // Tipo e local (equivalente ao bloco if item.local_execucao == 'EXTERNO' do Django)
            $localNome     = '';
            $codigoObra    = null;
            $codigoCliente = null;

            if ($item->local_execucao === 'EXTERNO') {
                $tipoStr = 'OBRA';
                if ($item->projeto) {
                    $localNome  = $item->projeto->nome;
                    $codigoObra = $item->projeto->codigo;
                } elseif ($item->codigoCliente) {
                    $localNome     = $item->codigoCliente->nome;
                    $codigoCliente = $item->codigoCliente->codigo;
                }
            } else {
                $tipoStr   = 'FORA DO SETOR';
                $localNome = $item->centroCusto?->nome ?? 'Atividade Interna';
                if ($item->projeto) {
                    $codigoObra = $item->projeto->codigo;
                } elseif ($item->codigoCliente) {
                    $codigoCliente = $item->codigoCliente->codigo;
                }
            }

            // Derivação de código cliente a partir do código da obra (equivalente ao Django)
            if ($codigoObra && strlen((string) $codigoObra) >= 5) {
                if (!$codigoCliente) {
                    $codigoCliente = substr((string) $codigoObra, 1, 4);
                }
            } elseif ($codigoObra && !$codigoCliente) {
                $codigoCliente = $codigoObra;
            }

            // Veículo
            $veiculoNome = '';
            $placa       = '';
            if ($item->veiculo) {
                $veiculoNome = $item->veiculo->descricao ?? (string) $item->veiculo;
                $placa       = $item->veiculo->placa ?? '';
            } elseif ($item->veiculo_manual_modelo) {
                $veiculoNome = $item->veiculo_manual_modelo;
                $placa       = $item->veiculo_manual_placa ?? '';
            }

            $fmtHora = fn($h) => $h ? substr($h, 0, 8) : null;
            $fmtData = fn($d) => $d ? Carbon::parse($d)->format('Y-m-d') : null;

            $baseObj = [
                'data'            => $fmtData($item->data_apontamento),
                'dia_semana'      => Carbon::parse($item->data_apontamento)->dayOfWeek,
                'tipo'            => $tipoStr,
                'local'           => $localNome,
                'codigo_obra'     => $codigoObra,
                'codigo_cliente'  => $codigoCliente,
                'hora_inicio'     => $fmtHora($item->hora_inicio),
                'hora_fim'        => $fmtHora($item->hora_termino),
                'observacoes'     => $item->ocorrencias,
                'registrado_por'  => $item->registradoPor?->email ?? 'Sistema',
                'dorme_fora'      => (bool) $item->dorme_fora,
                'em_plantao'      => (bool) $item->em_plantao,
                'status'          => $item->status_ajuste ?? 'OK',
            ];

            // Linha principal (colaborador)
            $rowMain = array_merge($baseObj, [
                'colaborador' => $item->colaborador->nome_completo,
                'cargo'       => $item->colaborador->cargo,
                'veiculo'     => $veiculoNome,
                'placa'       => $placa,
                'is_auxiliar' => false,
            ]);
            $dadosSaida[] = $rowMain;

            // Linhas de auxiliares (equivalente ao for aux in auxiliares do Django)
            $auxiliares = [];
            if ($item->auxiliar) {
                $auxiliares[] = $item->auxiliar;
            }
            $auxiliares = array_merge($auxiliares, $item->auxiliaresExtras->all());

            foreach ($auxiliares as $aux) {
                $rowAux = array_merge($baseObj, [
                    'colaborador' => $aux->nome_completo,
                    'cargo'       => $aux->cargo,
                    'veiculo'     => 'Passageiro',
                    'placa'       => null,
                    'is_auxiliar' => true,
                    'dorme_fora'  => (bool) $item->dorme_fora,
                    'em_plantao'  => (bool) $item->em_plantao,
                ]);
                $dadosSaida[] = $rowAux;
            }
        }

        return response()->json($dadosSaida);
    }

    /**
     * Health check de todas as dependências do sistema.
     * Equivalente ao health_check_view() do Django (apis.py L579-647).
     *
     * GET /api/health
     * Checa: database, wppconnect, feriados_api.
     */
    public function health(): JsonResponse
    {
        $healthStatus = [
            'status'       => 'healthy',
            'timestamp'    => now()->toIso8601String(),
            'dependencies' => [
                'database'        => 'offline',
                'wppconnect'      => 'offline',
                'feriados_api'    => 'offline',
                'solides_api'     => 'pending_integration',
            ],
        ];

        $statusCode = 200;

        // 1. Banco de dados (equivalente ao cursor.execute("SELECT 1") do Django)
        try {
            DB::select('SELECT 1');
            $healthStatus['dependencies']['database'] = 'online';
        } catch (\Throwable $e) {
            $healthStatus['status']                      = 'unhealthy';
            $healthStatus['dependencies']['database']    = "error: {$e->getMessage()}";
            $statusCode                                  = 503;
        }

        // 2. WPPConnect (Node.js) (equivalente ao requests.get(wpp_url/health))
        $wppUrl = config('services.wppconnect.base_url', 'http://localhost:3000');
        try {
            $respWpp = \Illuminate\Support\Facades\Http::timeout(3)->get("{$wppUrl}/health");
            if ($respWpp->ok()) {
                $fila = $respWpp->json('queueSize', 0);
                $healthStatus['dependencies']['wppconnect'] = "online (Fila: {$fila})";
            } else {
                $healthStatus['dependencies']['wppconnect'] = 'disconnected_or_starting';
                if ($healthStatus['status'] === 'healthy') {
                    $healthStatus['status'] = 'degraded';
                }
            }
        } catch (\Throwable) {
            $healthStatus['dependencies']['wppconnect'] = 'unreachable';
            if ($healthStatus['status'] === 'healthy') {
                $healthStatus['status'] = 'degraded';
            }
        }

        // 3. API de Feriados (equivalente ao requests.get(url_ping_feriados) do Django)
        try {
            $tokenFeriados = config('services.feriados.token');
            $anoAtual      = now()->year;
            $urlFeriados   = "https://www.feriadosapi.com/api/v1/feriados/cidade/3550308?ano={$anoAtual}";

            $headers = $tokenFeriados ? ['Authorization' => "Bearer {$tokenFeriados}"] : [];
            $respFeriados = \Illuminate\Support\Facades\Http::withHeaders($headers)->timeout(3)->get($urlFeriados);

            if ($respFeriados->ok()) {
                $healthStatus['dependencies']['feriados_api'] = 'online';
            } else {
                $healthStatus['dependencies']['feriados_api'] = "api_error (Status {$respFeriados->status()})";
                if ($healthStatus['status'] === 'healthy') {
                    $healthStatus['status'] = 'degraded';
                }
            }
        } catch (\Throwable) {
            $healthStatus['dependencies']['feriados_api'] = 'unreachable';
            if ($healthStatus['status'] === 'healthy') {
                $healthStatus['status'] = 'degraded';
            }
        }

        return response()->json($healthStatus, $statusCode);
    }
}
