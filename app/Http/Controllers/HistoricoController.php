<?php

namespace App\Http\Controllers;

use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\Colaborador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HistoricoController
 *
 * Equivalente à view Django:
 *   historico_apontamentos_view() → index()
 *
 * Listagem com filtros de data, permissões RBAC de visualização
 * e montagem da lista enriquecida (totais diários, veículo display, etc.)
 */
class HistoricoController extends Controller
{
    /**
     * Lista o histórico de apontamentos com filtros e agrupamentos.
     * Equivalente ao historico_apontamentos_view() do Django.
     *
     * GET /historico
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $ehAdmin  = AcessoHelper::isAdmin($user);
        $ehOwner  = AcessoHelper::isOwner($user);
        $ehGestor = AcessoHelper::isGerente($user);
        $podeVerAlertas = $ehOwner || $ehGestor;

        $isOperacional = $user->hasRole('OPERACIONAL');

        // ─── Filtros de Data e Outros Parâmetros ───────────────────────────────
        $dias = $request->query('dias');
        $startDateStr = $request->query('start_date');
        $endDateStr   = $request->query('end_date');

        if ($isOperacional) {
            if ($dias && in_array($dias, [3, 15, 30])) {
                $startDateStr = now()->subDays($dias - 1)->toDateString();
                $endDateStr   = now()->toDateString();
            } else {
                if (empty($startDateStr) || empty($endDateStr)) {
                    $startDateStr = now()->subDays(2)->toDateString();
                    $endDateStr   = now()->toDateString();
                } else {
                    $start = \Carbon\Carbon::parse($startDateStr);
                    $end = \Carbon\Carbon::parse($endDateStr);
                    if ($start->diffInDays($end) > 30) {
                        $startDateStr = (clone $end)->subDays(30)->toDateString();
                    }
                }
            }
        } else {
            // Filtro padrão para outros perfis: últimos 3 dias
            if (empty($startDateStr)) {
                $startDateStr = now()->subDays(2)->toDateString();
            }
            if (empty($endDateStr)) {
                $endDateStr = now()->toDateString();
            }
        }

        // ─── Query Base ────────────────────────────────────────────────────────
        $query = Apontamento::with([
            'colaborador',
            'projeto',
            'codigoCliente',
            'centroCusto',
            'veiculo',
            'registradoPor',
            'auxiliar',
            'auxiliaresExtras',
            'aprovador',
            'diariosObra',
        ])
        ->orderByDesc('data_apontamento')
        ->orderBy('colaborador_id')
        ->orderByDesc('hora_termino');

        // Filtros de Data (Início e Fim)
        $query->whereDate('data_apontamento', '>=', $startDateStr)
              ->whereDate('data_apontamento', '<=', $endDateStr);

        // Filtros combinados (Ignorados para OPERACIONAL)
        if (!$isOperacional) {
            $query->when($request->query('colaborador_id'), function ($q, $val) {
                $q->where('colaborador_id', $val);
            })
            ->when($request->query('projeto_id'), function ($q, $val) {
                $q->where('projeto_id', $val);
            })
            ->when($request->query('codigo_cliente_id'), function ($q, $val) {
                $q->where('codigo_cliente_id', $val);
            })
            ->when($request->query('centro_custo_id'), function ($q, $val) {
                $q->where('centro_custo_id', $val);
            })
            ->when($request->query('veiculo_id'), function ($q, $val) {
                $q->where('veiculo_id', $val);
            })
            ->when($request->query('status'), function ($q, $val) {
                $q->where('status_aprovacao', $val);
            });
        }

        // ─── Filtro de Visibilidade (Row-Level Security) ───────────
        $query->visibilidadePermitida($user);

        $bloqueiaDataAntiga = false;
        $limitDate = now()->subDays(30)->toDateString();

        if (!$ehOwner) {
            if ($startDateStr < $limitDate) {
                $bloqueiaDataAntiga = true;
            }
            $query->where('data_apontamento', '>=', $limitDate);
        }

        $apontamentos = $query->paginate(50)->withQueryString();

        // ─── Cálculo de Totais por Dia/Colaborador (equivalente ao Django) ─────
        $mapaTotaisSegundos = [];
        foreach ($apontamentos as $item) {
            if (!$item->hora_inicio || !$item->hora_termino) {
                continue;
            }
            $key = "{$item->colaborador_id}|{$item->data_apontamento}";
            $ini = Carbon::parse("2000-01-01 {$item->hora_inicio}");
            $fim = Carbon::parse("2000-01-01 {$item->hora_termino}");
            if ($fim->lt($ini)) {
                $fim->addDay();
            }
            $mapaTotaisSegundos[$key] = ($mapaTotaisSegundos[$key] ?? 0) + abs($ini->diffInSeconds($fim));
        }

        // ─── Monta Lista Enriquecida ────────────────────────────────────────────
        $historicoLista      = [];
        $chavesJaExibidas    = [];

        foreach ($apontamentos as $item) {
            // Local Ref (equivalente ao bloco de formatação do Django)
            if ($item->local_execucao === 'EXTERNO') {
                $localTipoDisplay = 'DENTRO DA OBRA';
                if ($item->projeto) {
                    $cod      = $item->projeto->codigo ?? '';
                    $localRef = $cod ? "{$cod} - {$item->projeto->nome}" : $item->projeto->nome;
                } elseif ($item->codigoCliente) {
                    $localRef = "{$item->codigoCliente->codigo} - {$item->codigoCliente->nome}";
                } else {
                    $localRef = 'Obra/Cliente não informado';
                }
            } else {
                $localTipoDisplay = 'FORA DA OBRA';
                if ($item->projeto) {
                    $localRef = " {$item->projeto->codigo} - {$item->projeto->nome} ({$item->centroCusto?->nome})";
                } elseif ($item->codigoCliente) {
                    $localRef = " {$item->codigoCliente->codigo} - {$item->codigoCliente->nome} ({$item->centroCusto?->nome})";
                } else {
                    $localRef = " {$item->centroCusto?->nome}";
                }
            }

            // Veículo display
            if ($item->veiculo) {
                $veiculoDisplay = (string) $item->veiculo;
            } elseif ($item->veiculo_manual_placa) {
                $veiculoDisplay = "{$item->veiculo_manual_modelo} - {$item->veiculo_manual_placa} (Externo)";
            } else {
                $veiculoDisplay = '';
            }

            // Registrado por
            $regUser = $item->registradoPor;
            $userDisplay = $regUser
                ? ($regUser->name ?: $regUser->email)
                : 'Sistema';

            // Total do dia
            $key = "{$item->colaborador_id}|{$item->data_apontamento}";
            $isFistOfDay = !in_array($key, $chavesJaExibidas, true);
            if ($isFistOfDay) {
                $chavesJaExibidas[] = $key;
            }

            $textTotalDia = '';
            $corTotalDia  = 'text-gray-500';

            if ($isFistOfDay) {
                $secs   = $mapaTotaisSegundos[$key] ?? 0;
                $horas  = (int) ($secs / 3600);
                $mins   = (int) (($secs % 3600) / 60);
                $textTotalDia = sprintf('%02d:%02d', $horas, $mins);

                $cargo = strtoupper($item->colaborador->cargo ?? '');
                if (!str_contains($cargo, 'JOVEM APRENDIZ')) {
                    if ($secs < 31000) {
                        $corTotalDia = 'text-orange-400 font-bold';
                    } elseif ($secs > 32400) {
                        $corTotalDia = 'text-blue-400 font-bold';
                    } else {
                        $corTotalDia = 'text-emerald-400 font-bold';
                    }
                } else {
                    $corTotalDia = 'text-gray-400 font-bold';
                }
            }

            $exibirAlerta = $item->flag_atencao && $podeVerAlertas;

            // Linha principal (equivalente ao row_main do Django)
            $rowMain = [
                'id'                 => $item->id,
                'nome'               => $item->colaborador->nome_completo,
                'cargo'              => $item->cargo_snapshot ?? $item->colaborador->cargo,
                'data'               => $item->data_apontamento,
                'local_ref'          => $localRef,
                'unidade_ref'        => $item->projeto ? $item->projeto->unidade : null,
                'local_tipo'         => $localTipoDisplay,
                'inicio'             => $item->hora_inicio,
                'termino'            => $item->hora_termino,
                'duracao'            => $item->duracao_total_str,
                'veiculo'            => $veiculoDisplay,
                'obs'                => $item->ocorrencias,
                'registrado_em'      => $item->created_at,
                'registrado_por_str' => $userDisplay,
                'registrado_por_id'  => $item->registrado_por_id,
                'em_plantao'         => $item->em_plantao,
                'dorme_fora'         => $item->dorme_fora,
                'motivo_ajuste'      => $item->motivo_ajuste,
                'status_ajuste'      => $item->status_ajuste,
                'status_aprovacao'   => $item->status_aprovacao,
                'contagem_edicao'    => $item->contagem_edicao,
                'pode_editar'        => $ehAdmin || $ehOwner || (($item->contagem_edicao < 1 || $item->status_ajuste === 'APROVADO') && $item->registrado_por_id === $user->id),
                'pode_solicitar_ajuste' => ($item->status_aprovacao ?? 'EM_ANALISE') !== 'SOLICITACAO_AJUSTE' && ($item->registrado_por_id === $user->id || $ehOwner),
                'motivo_rejeicao'    => $item->motivo_rejeicao,
                'latitude'           => $item->latitude,
                'longitude'          => $item->longitude,
                'is_last_of_day'     => $isFistOfDay,
                'total_dia_str'      => $textTotalDia,
                'total_dia_class'    => $corTotalDia,
                'flag_atencao'       => $exibirAlerta,
                'motivo_alerta'      => $exibirAlerta ? $item->motivo_alerta : null,
                'is_auxiliar'        => false,
                'id_agrupamento'     => $item->id_agrupamento,
                // Auditoria de Aprovação
                'tipo_aprovacao'     => $item->tipo_aprovacao,
                'aprovador_nome'     => $item->aprovador?->name ?? null,
                'data_aprovacao'     => $item->data_aprovacao,
                'texto_diario'       => $item->diariosObra->first()?->texto_diario,
            ];

            $historicoLista[] = $rowMain;

            // Linhas de auxiliares (equivalente ao for aux in auxiliares_a_exibir do Django)
            $auxiliares = [];
            if ($item->auxiliar) {
                $auxiliares[] = $item->auxiliar;
            }
            $auxiliares = array_merge($auxiliares, $item->auxiliaresExtras->all());

            foreach ($auxiliares as $aux) {
                $rowAux = $rowMain;
                $rowAux['nome']          = $aux->nome_completo;
                $rowAux['cargo']         = $aux->cargo; // Auxiliares mantêm cargo atual na view
                $rowAux['veiculo']       = '';
                $rowAux['is_auxiliar']   = true;
                $rowAux['is_last_of_day'] = false;
                $rowAux['flag_atencao']  = false;
                $historicoLista[]        = $rowAux;
            }
        }

        // ─── Carregamento de Coleções para os Filtros (Preparação para Select2) ───
        $colaboradoresOptions = [];
        $projetosOptions = [];
        $clientesOptions = [];
        $centrosCustoOptions = [];
        $veiculosOptions = [];

        if (!$isOperacional) {
            $colaboradoresOptions = Colaborador::all()->sortBy('nome_completo')->pluck('nome_completo', 'id')->toArray();
            
            $projetosRaw = \App\Models\ProjetoOperacional::with('cliente')->where('ativo', true)->get()->sortBy('codigo');
            foreach ($projetosRaw as $proj) {
                $label = $proj->codigo . ' - ' . $proj->nome;
                if (!empty($proj->unidade) && !str_contains($proj->unidade, 'N/A')) {
                    $label .= ' [' . $proj->unidade . ']';
                }
                $projetosOptions[$proj->id] = $label;
            }

            $clientesRaw = \App\Models\ClienteOperacional::where('ativo', true)->orderBy('nome')->get();
            foreach ($clientesRaw as $cli) {
                $clientesOptions[$cli->id] = $cli->codigo . ' - ' . $cli->nome;
            }

            $centrosCustoRaw = \App\Models\CentroCusto::where('ativo', true)->orderBy('nome')->get();
            foreach ($centrosCustoRaw as $cc) {
                $centrosCustoOptions[$cc->id] = $cc->nome;
            }

            $veiculosRaw = \App\Models\Veiculo::orderBy('placa')->get();
            foreach ($veiculosRaw as $veic) {
                $veiculosOptions[$veic->id] = $veic->placa . ' - ' . $veic->descricao;
            }
        }

        return view('historico', [
            'titulo'              => 'Histórico',
            'apontamentos_lista'  => $historicoLista,
            'paginador'           => $apontamentos,
            'show_user_column'    => $ehOwner,
            'is_owner'            => $ehOwner,
            'is_gestor'           => $ehGestor,
            'start_date_val'      => $startDateStr,
            'end_date_val'        => $endDateStr,
            'bloqueia_data_antiga'=> $bloqueiaDataAntiga,
            'colaboradoresOpts'   => $colaboradoresOptions,
            'projetosOpts'        => $projetosOptions,
            'clientesOpts'        => $clientesOptions,
            'centrosCustoOpts'    => $centrosCustoOptions,
            'veiculosOpts'        => $veiculosOptions,
        ]);
    }
}
