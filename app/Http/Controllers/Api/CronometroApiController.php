<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Services\AuditoriaService;
use App\Services\ConformidadeCLTService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CronometroApiController
 *
 * Equivalente às APIs Django:
 *   api_iniciar_cronometro(request)  → iniciar()   POST /api/timer/start
 *   api_parar_cronometro(request)    → parar()     POST /api/timer/stop
 *   api_status_cronometro(request)   → status()    GET  /api/timer/status
 *
 * Fluxo Check-in/Check-out via AJAX — usado pelo widget de cronômetro no front-end.
 */
class CronometroApiController extends Controller
{
    /**
     * Inicia o cronômetro (Check-in).
     * Equivalente ao api_iniciar_cronometro() do Django (apis.py L72-147).
     *
     * POST /api/timer/start
     *
     * Payload esperado (JSON ou form):
     *   colaborador_id, data_apontamento, local_execucao,
     *   projeto_id?, codigo_cliente_id?, centro_custo_id?,
     *   veiculo_selecao?, auxiliar_id?, (campos opcionais)
     */
    public function iniciar(\App\Http\Requests\ApontamentoRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Equivalente ao Colaborador.objects.get(user_account=request.user)
        $colaborador = $user?->colaborador;
        if (!$colaborador) {
            return response()->json([
                'success' => false,
                'error'   => 'Usuário sem perfil de colaborador vinculado.',
            ], 422);
        }

        // Bloqueia novo check-in se já há um em aberto
        if (Apontamento::where('colaborador_id', $colaborador->id)->whereNull('hora_termino')->exists()) {
            return response()->json([
                'success' => false,
                'error'   => 'Você já possui uma atividade em andamento.',
            ], 409);
        }

        $dados = $request->dadosLimpos();
        
        $agora = now(); // timezone America/Sao_Paulo

        // Monta o apontamento
        $ap = new Apontamento();
        $ap->colaborador_id    = $dados['colaborador_id'] ?? $colaborador->id;
        $ap->data_apontamento  = $dados['data_apontamento'] ?? $agora->toDateString();
        $ap->hora_inicio       = isset($dados['hora_inicio']) ? $dados['hora_inicio'] . ':00' : $agora->format('H:i:s');
        $ap->hora_termino      = null;  // Check-in — sem hora_termino
        $ap->local_execucao    = $dados['local_execucao'];
        $ap->projeto_id        = $dados['projeto_id'] ?? null;
        $ap->codigo_cliente_id = $dados['codigo_cliente_id'] ?? null;
        $ap->centro_custo_id   = $dados['centro_custo_id'] ?? null;
        $ap->ocorrencias       = $dados['ocorrencias'] ?? null;
        $ap->latitude          = $dados['latitude'] ?? null;
        $ap->longitude         = $dados['longitude'] ?? null;
        $ap->registrado_por_id = $user->id;
        $ap->status_aprovacao  = 'EM_ANALISE';
        $ap->contagem_edicao   = 0;

        // Veículo
        $ap->veiculo_id            = $dados['veiculo_id'] ?? null;
        $ap->veiculo_manual_modelo = $dados['veiculo_manual_modelo'] ?? null;
        $ap->veiculo_manual_placa  = $dados['veiculo_manual_placa'] ?? null;

        // Auxiliar principal
        $ap->auxiliar_id = filter_var($dados['registrar_auxiliar'] ?? false, FILTER_VALIDATE_BOOLEAN) 
                            ? ($dados['auxiliar_id'] ?? null) 
                            : null;

        $ap->save();

        // Auxiliares extras M2M
        $auxiliares = $request->input('auxiliares', []);
        if (is_array($auxiliares) && count($auxiliares) > 0) {
            $ids = array_filter(array_map('intval', $auxiliares), fn($id) => $id > 0);
            $ap->auxiliaresExtras()->sync($ids);
        } else {
            $ap->auxiliaresExtras()->sync([]);
        }

        AuditoriaService::registrar($request, 'CRIACAO', 'Apontamento', $ap->id, "Check-in iniciado via cronômetro às {$agora->format('H:i')}");

        return response()->json([
            'success' => true,
            'message' => 'Atividade iniciada!',
            'inicio'  => $agora->format('H:i'),
            'id'      => $ap->id,
        ]);
    }

    /**
     * Para o cronômetro (Check-out).
     * Equivalente ao api_parar_cronometro() do Django (apis.py L149-190).
     *
     * POST /api/timer/stop
     *
     * Owner pode parar o timer de outro colaborador (colaborador_id no body).
     */
    public function parar(Request $request): JsonResponse
    {
        $user      = auth()->user();
        $targetId  = $request->input('colaborador_id');

        // Owner pode encerrar timer de outro colaborador (equivalente ao Django)
        if (AcessoHelper::isOwner($user) && $targetId) {
            $colaborador = Colaborador::find((int) $targetId);
            if (!$colaborador) {
                return response()->json(['success' => false, 'error' => 'Colaborador não encontrado.'], 404);
            }
        } else {
            $colaborador = $user?->colaborador;
            if (!$colaborador) {
                return response()->json(['success' => false, 'error' => 'Colaborador não encontrado.'], 404);
            }
        }

        $apontamento = Apontamento::where('colaborador_id', $colaborador->id)
            ->whereNull('hora_termino')
            ->orderByDesc('id')
            ->first();

        if (!$apontamento) {
            $nome = $colaborador->nome_completo ?? 'Colaborador';
            return response()->json([
                'success' => false,
                'error'   => "Nenhuma atividade em andamento encontrada para {$nome}.",
            ], 404);
        }

        $agora = now();
        $apontamento->hora_termino = $agora->format('H:i:s');
        $apontamento->save();

        // Recalcula CLT após check-out (equivalente ao Django)
        try {
            $dtContabil = ConformidadeCLTService::getDataContabil(
                Carbon::parse("{$apontamento->data_apontamento} {$apontamento->hora_inicio}")
            );
            ConformidadeCLTService::calcularRegrasClt($colaborador, $dtContabil);
        } catch (\Throwable $e) {
            // Falha silenciosa (equivalente ao print() do Django)
            \Illuminate\Support\Facades\Log::warning("Erro CLT no stop timer: {$e->getMessage()}");
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Atividade finalizada!',
            'termino'  => $agora->format('H:i'),
            'duracao'  => $apontamento->duracao_total_str ?? 'Calculando...',
        ]);
    }

    /**
     * Retorna o status atual do cronômetro do usuário logado.
     * Equivalente ao api_status_cronometro() do Django (apis.py L192-235).
     *
     * GET /api/timer/status
     *
     * Payload de resposta quando ativo:
     *   ativo, inicio_timestamp, inicio_str, data_registro,
     *   colaborador_id, colaborador_nome, veiculo_id, veiculo_nome,
     *   projeto_nome, projeto_id, cliente_nome, cliente_id,
     *   cc_nome, cc_id, local
     */
    public function status(): JsonResponse
    {
        try {
            $user       = auth()->user();
            $colaborador = $user?->colaborador;

            if (!$colaborador) {
                return response()->json(['ativo' => false]);
            }

            $apontamento = Apontamento::with(['veiculo', 'projeto', 'codigoCliente', 'centroCusto', 'colaborador'])
                ->where('colaborador_id', $colaborador->id)
                ->whereNull('hora_termino')
                ->orderByDesc('id')
                ->first();

            if (!$apontamento) {
                return response()->json(['ativo' => false]);
            }

            // Calcula o timestamp de início (equivalente ao Django)
            $dtInicio    = Carbon::parse("{$apontamento->data_apontamento} {$apontamento->hora_inicio}");
            $inicioTs    = $dtInicio->timestamp;

            // Veículo ID para repopular o formulário (equivalente ao Django)
            $veiculoId = null;
            if ($apontamento->veiculo_id) {
                $veiculoId = $apontamento->veiculo_id;
            } elseif ($apontamento->veiculo_manual_modelo || $apontamento->veiculo_manual_placa) {
                $veiculoId = 'OUTRO';
            }

            return response()->json([
                'ativo'             => true,
                'inicio_timestamp'  => $inicioTs,
                'inicio_str'        => substr($apontamento->hora_inicio, 0, 5),
                'data_registro'     => Carbon::parse($apontamento->data_apontamento)->format('d/m/Y'),
                'colaborador_id'    => $apontamento->colaborador_id,
                'colaborador_nome'  => $apontamento->colaborador->nome_completo,
                'veiculo_id'        => $veiculoId,
                'veiculo_nome'      => $apontamento->veiculo
                    ? (string) $apontamento->veiculo
                    : 'Veículo Manual',
                'projeto_nome'      => $apontamento->projeto ? $apontamento->projeto->nome : null,
                'projeto_id'        => $apontamento->projeto_id,
                'cliente_nome'      => $apontamento->codigoCliente ? $apontamento->codigoCliente->nome : null,
                'cliente_id'        => $apontamento->codigo_cliente_id,
                'cc_nome'           => $apontamento->centroCusto ? $apontamento->centroCusto->nome : null,
                'cc_id'             => $apontamento->centro_custo_id,
                'local'             => $apontamento->local_execucao,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['ativo' => false, 'error' => $e->getMessage()]);
        }
    }
}
