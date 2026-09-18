<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Apontamento;
use App\Helpers\AcessoHelper;

class RelatorioController extends Controller
{
    /**
     * Aplica o filtro de segurança de Row Level Security (RLS)
     * e retorna a query base de apontamentos isolada.
     */
    private function getApontamentosBaseQuery(Carbon $dataInicial, Carbon $dataFinal)
    {
        $query = Apontamento::with([
            'colaborador', 
            'projeto', 
            'codigoCliente', 
            'centroCusto', 
            'auxiliar',
            'auxiliaresExtras'
        ])
        ->whereBetween('data_apontamento', [$dataInicial->format('Y-m-d'), $dataFinal->format('Y-m-d')]);

        $user = auth()->user();

        if (!AcessoHelper::isAdmin($user)) {
            if (AcessoHelper::isAcessoExpandido($user)) {
                $setoresIds = $user->colaborador->getSetoresPermitidosIds();
                $query->whereHas('colaborador', function ($q) use ($setoresIds) {
                    $q->whereIn('setor_id', $setoresIds);
                });
            } else {
                $query->where('colaborador_id', $user->colaborador->id ?? 0);
            }
        }

        return $query;
    }
    /**
     * Exibe a tela de extração de relatórios.
     */
    public function index(Request $request)
    {


        $dadosRelatorio = null;

        $tipoRelatorio = $request->input('tipo_relatorio');

        if ($tipoRelatorio === 'dorme_fora' && $request->has('data_inicial') && $request->has('data_final')) {
            $dataInicial = Carbon::parse($request->input('data_inicial'));
            $dataFinal = Carbon::parse($request->input('data_final'));

            $apontamentos = $this->getApontamentosBaseQuery($dataInicial, $dataFinal)
                ->where('dorme_fora', true)
                ->get();

            $dadosAchatados = collect();

            foreach ($apontamentos as $ap) {
                $dataFormatada = Carbon::parse($ap->data_apontamento)->locale('pt_BR')->translatedFormat('l - d/m/Y');
                $codigoLocal = $ap->projeto->codigo ?? $ap->codigoCliente->codigo ?? 'N/A';
                $nomeLocal = $ap->projeto->nome ?? $ap->codigoCliente->nome ?? $ap->centroCusto->nome ?? 'N/A';

                if ($ap->colaborador) {
                    $dadosAchatados->push((object)[
                        'nome_colaborador' => $ap->colaborador->nome_completo ?? 'N/A',
                        'cargo'            => $ap->colaborador->cargo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'codigo_local'     => $codigoLocal,
                        'nome_local'       => $nomeLocal,
                    ]);
                }

                if ($ap->auxiliar) {
                    $dadosAchatados->push((object)[
                        'nome_colaborador' => $ap->auxiliar->nome_completo ?? $ap->auxiliar->nome ?? 'N/A',
                        'cargo'            => $ap->auxiliar->cargo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'codigo_local'     => $codigoLocal,
                        'nome_local'       => $nomeLocal,
                    ]);
                }

                foreach ($ap->auxiliaresExtras as $aux) {
                    $dadosAchatados->push((object)[
                        'nome_colaborador' => $aux->nome_completo ?? $aux->nome ?? 'N/A',
                        'cargo'            => $aux->cargo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'codigo_local'     => $codigoLocal,
                        'nome_local'       => $nomeLocal,
                    ]);
                }
            }

            $dadosRelatorio = $dadosAchatados->groupBy('nome_colaborador');
        } elseif ($tipoRelatorio === 'sefip' && $request->has('data_inicial') && $request->has('data_final')) {
            $dataInicial = Carbon::parse($request->input('data_inicial'));
            $dataFinal = Carbon::parse($request->input('data_final'));

            $apontamentos = $this->getApontamentosBaseQuery($dataInicial, $dataFinal)
                ->where('local_execucao', 'EXTERNO')
                ->get();

            $dadosAchatados = collect();

            foreach ($apontamentos as $ap) {
                $dataFormatada = Carbon::parse($ap->data_apontamento)->locale('pt_BR')->translatedFormat('d/m/Y');
                $codigoLocal = $ap->projeto->codigo ?? $ap->codigoCliente->codigo ?? 'N/A';
                $nomeLocal = $ap->projeto->nome ?? $ap->codigoCliente->nome ?? $ap->centroCusto->nome ?? 'N/A';
                
                // Calcula horas totais (diferença entre inicio e término)
                $inicio = Carbon::parse($ap->hora_inicio);
                $termino = Carbon::parse($ap->hora_termino);
                $horasTotais = $inicio->diff($termino)->format('%H:%I');

                $chaveAgrupamento = $codigoLocal . '|||' . $nomeLocal; // chave unificada para o groupBy

                if ($ap->colaborador) {
                    $dadosAchatados->push((object)[
                        'chave_local'      => $chaveAgrupamento,
                        'nome_colaborador' => $ap->colaborador->nome_completo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'horas_totais'     => $horasTotais,
                    ]);
                }

                if ($ap->auxiliar) {
                    $dadosAchatados->push((object)[
                        'chave_local'      => $chaveAgrupamento,
                        'nome_colaborador' => $ap->auxiliar->nome_completo ?? $ap->auxiliar->nome ?? 'N/A',
                        'data'             => $dataFormatada,
                        'horas_totais'     => $horasTotais,
                    ]);
                }

                foreach ($ap->auxiliaresExtras as $aux) {
                    $dadosAchatados->push((object)[
                        'chave_local'      => $chaveAgrupamento,
                        'nome_colaborador' => $aux->nome_completo ?? $aux->nome ?? 'N/A',
                        'data'             => $dataFormatada,
                        'horas_totais'     => $horasTotais,
                    ]);
                }
            }

            $dadosRelatorio = $dadosAchatados->groupBy('chave_local');
        }

        return view('relatorios.index', compact('dadosRelatorio', 'tipoRelatorio'));
    }

    /**
     * Extrai o relatório dinamicamente.
     */
    public function exportar(Request $request)
    {
        $request->validate([
            'data_inicial' => 'required|date',
            'data_final'   => 'required|date|after_or_equal:data_inicial',
            'tipo_relatorio' => 'required|string',
        ]);

        $tipoRelatorio = $request->input('tipo_relatorio');
        $dataInicial = Carbon::parse($request->input('data_inicial'));
        $dataFinal = Carbon::parse($request->input('data_final'));

        $apontamentosQuery = $this->getApontamentosBaseQuery($dataInicial, $dataFinal);

        if ($tipoRelatorio === 'dorme_fora') {
            $apontamentos = $apontamentosQuery->where('dorme_fora', true)->get();

            $dadosAchatados = collect();

            foreach ($apontamentos as $ap) {
                $dataFormatada = Carbon::parse($ap->data_apontamento)->locale('pt_BR')->translatedFormat('l - d/m/Y');
                $codigoLocal = $ap->projeto->codigo ?? $ap->codigoCliente->codigo ?? 'N/A';
                $nomeLocal = $ap->projeto->nome ?? $ap->codigoCliente->nome ?? $ap->centroCusto->nome ?? 'N/A';

                if ($ap->colaborador) {
                    $dadosAchatados->push((object)[
                        'nome_colaborador' => $ap->colaborador->nome_completo ?? 'N/A',
                        'cargo'            => $ap->colaborador->cargo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'codigo_local'     => $codigoLocal,
                        'nome_local'       => $nomeLocal,
                    ]);
                }

                if ($ap->auxiliar) {
                    $dadosAchatados->push((object)[
                        'nome_colaborador' => $ap->auxiliar->nome_completo ?? $ap->auxiliar->nome ?? 'N/A',
                        'cargo'            => $ap->auxiliar->cargo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'codigo_local'     => $codigoLocal,
                        'nome_local'       => $nomeLocal,
                    ]);
                }

                foreach ($ap->auxiliaresExtras as $aux) {
                    $dadosAchatados->push((object)[
                        'nome_colaborador' => $aux->nome_completo ?? $aux->nome ?? 'N/A',
                        'cargo'            => $aux->cargo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'codigo_local'     => $codigoLocal,
                        'nome_local'       => $nomeLocal,
                    ]);
                }
            }

            $dadosRelatorio = $dadosAchatados->groupBy('nome_colaborador');

            return view('relatorios.imprimir-dorme-fora', compact('dadosRelatorio', 'dataInicial', 'dataFinal'));
        } elseif ($tipoRelatorio === 'sefip') {
            $apontamentos = $apontamentosQuery->where('local_execucao', 'EXTERNO')->get();

            $dadosAchatados = collect();

            foreach ($apontamentos as $ap) {
                $dataFormatada = Carbon::parse($ap->data_apontamento)->locale('pt_BR')->translatedFormat('d/m/Y');
                $codigoLocal = $ap->projeto->codigo ?? $ap->codigoCliente->codigo ?? 'N/A';
                $nomeLocal = $ap->projeto->nome ?? $ap->codigoCliente->nome ?? $ap->centroCusto->nome ?? 'N/A';
                
                // Calcula horas totais (diferença entre inicio e término)
                $inicio = Carbon::parse($ap->hora_inicio);
                $termino = Carbon::parse($ap->hora_termino);
                $horasTotais = $inicio->diff($termino)->format('%H:%I');

                $chaveAgrupamento = $codigoLocal . '|||' . $nomeLocal; // chave unificada

                if ($ap->colaborador) {
                    $dadosAchatados->push((object)[
                        'chave_local'      => $chaveAgrupamento,
                        'nome_colaborador' => $ap->colaborador->nome_completo ?? 'N/A',
                        'data'             => $dataFormatada,
                        'horas_totais'     => $horasTotais,
                    ]);
                }

                if ($ap->auxiliar) {
                    $dadosAchatados->push((object)[
                        'chave_local'      => $chaveAgrupamento,
                        'nome_colaborador' => $ap->auxiliar->nome_completo ?? $ap->auxiliar->nome ?? 'N/A',
                        'data'             => $dataFormatada,
                        'horas_totais'     => $horasTotais,
                    ]);
                }

                foreach ($ap->auxiliaresExtras as $aux) {
                    $dadosAchatados->push((object)[
                        'chave_local'      => $chaveAgrupamento,
                        'nome_colaborador' => $aux->nome_completo ?? $aux->nome ?? 'N/A',
                        'data'             => $dataFormatada,
                        'horas_totais'     => $horasTotais,
                    ]);
                }
            }

            $dadosRelatorio = $dadosAchatados->groupBy('chave_local');
            $dadosManuais = $request->input('dados_manuais', []);

            return view('relatorios.imprimir-sefip', compact('dadosRelatorio', 'dataInicial', 'dataFinal', 'dadosManuais'));
        }

        return back()->with('error', 'Tipo de relatório não implementado.');
    }
}
