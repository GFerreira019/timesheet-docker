<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;
use App\Services\SolidesService;
use Carbon\Carbon;

class PontoController extends Controller
{
    /**
     * Exibe a view de busca e a tabela do espelho de ponto.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $colab = $user->colaborador;

        // 1. Carregar lista de colaboradores com a mesma regra da tela de Apontamentos
        $query = Colaborador::ativos()->whereHas('setorRelacionamento', fn($s) => $s->where('ativo', true));

        if (!\App\Helpers\AcessoHelper::isAdmin($user)) {
            if (\App\Helpers\AcessoHelper::isCoordenador($user) || \App\Helpers\AcessoHelper::isAdministrativo($user)) {
                if (!$colab) {
                    $query->whereRaw('0=1');
                } else {
                    $setoresGerenciados = $colab->setoresGerenciados()->pluck('setores.id');
                    if ($setoresGerenciados->isEmpty()) {
                        $query->where('id', $colab->id);
                    } else {
                        $query->where(function ($q) use ($setoresGerenciados, $colab) {
                            $q->whereIn('setor_id', $setoresGerenciados)
                              ->orWhere('id', $colab->id);
                        });
                    }
                }
            } else {
                if (!$colab) {
                    $query->whereRaw('0=1');
                } elseif (\App\Helpers\AcessoHelper::isAcessoExpandido($user)) {
                    $setoresVinculados = $colab->setoresVinculados()->pluck('setores.id');
                    $query->where(function ($q) use ($setoresVinculados, $colab) {
                        $q->whereIn('setor_id', $setoresVinculados)
                          ->orWhere('id', $colab->id);
                    });
                } else {
                    $query->where('id', $colab->id);
                }
            }
        }

        $colaboradores = $query->distinct()
            ->select('id', 'nome_completo','cargo')
            ->orderBy('nome_completo')
            ->get();

        $pontos = null;
        
        $colaboradorId = $request->input('colaborador_id');
        // Filtro Obrigatório do Mês (default para o atual se vazio)
        $mesAno = $request->input('mes_ano') ?: date('Y-m');

        if ($colaboradorId) {
            try {
                // Passando instâncias do Carbon para precisão e conversão no Service
                $inicioMesCarbon = Carbon::parse($mesAno . '-01')->startOfMonth();
                $fimMesCarbon = Carbon::parse($mesAno . '-01')->endOfMonth();

                // 2. Sincronizar o espelho de ponto na API da Sólides
                SolidesService::buscarEspelhoPonto($colaboradorId, $inicioMesCarbon, $fimMesCarbon);

                // 3. Buscar os registros do banco de dados ordenados por data decrescente
                $pontosBrutos = \App\Models\SolidesPonto::with('colaborador')
                    ->where('colaborador_id', $colaboradorId)
                    ->whereBetween('data', [$inicioMesCarbon->format('Y-m-d'), $fimMesCarbon->format('Y-m-d')])
                    ->orderBy('data', 'desc')
                    ->orderBy('hora_entrada', 'asc')
                    ->get();
                
                $pontos = $pontosBrutos->groupBy(function($item) {
                    return $item->data->format('Y-m-d');
                })->map(function($turnos) {
                    $saldoMinutos = 0;
                    $horasAbonadasDia = 0;
                    $justificativas = [];
                    
                    $turnosMapeados = $turnos->map(function($turno) use (&$saldoMinutos, &$horasAbonadasDia, &$justificativas) {
                        if (!$turno->is_ajustado && $turno->hora_entrada && $turno->hora_saida) {
                            $entrada = Carbon::parse($turno->hora_entrada);
                            $saida = Carbon::parse($turno->hora_saida);
                            $saldoMinutos += $entrada->diffInMinutes($saida);
                        }
                        
                        // Lógica da nova regra de negócio para abonos
                        if ($turno->is_ajustado) {
                            if ($turno->horas_abonadas) {
                                $horasAbonadasDia += (float) $turno->horas_abonadas;
                            }
                            if ($turno->justificativa) {
                                $justificativas[] = $turno->justificativa;
                            }
                            
                            // Limpa horários para não exibir batidas falsas na view
                            $turno->hora_entrada = null;
                            $turno->hora_saida = null;
                        }

                        return $turno;
                    });
                    
                    $horas = floor($saldoMinutos / 60);
                    $minutos = $saldoMinutos % 60;
                    
                    $horasAbDecimal = $horasAbonadasDia;
                    if ($horasAbDecimal > 100) {
                        $horasAbDecimal = $horasAbDecimal / 1000;
                    }
                    
                    $horasAb = floor($horasAbDecimal);
                    $minutosAb = round(($horasAbDecimal - $horasAb) * 60);
                    $horasAbonadasDiaStr = $horasAbonadasDia > 0 ? sprintf('%02d:%02d', $horasAb, $minutosAb) : '-';
                    
                    return [
                        'data' => $turnos->first()->data,
                        'turnos' => $turnosMapeados->values(),
                        'saldo_dia' => sprintf('%02d:%02d', $horas, $minutos),
                        'horas_abonadas_dia' => $horasAbonadasDiaStr,
                        'justificativas' => array_unique($justificativas)
                    ];
                });

            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['api' => 'Erro ao consultar espelho de ponto: ' . $e->getMessage()]);
            }
        }

        return view('pontos.index', compact('colaboradores', 'pontos', 'colaboradorId', 'mesAno'));
    }

    /**
     * Sincroniza os pontos da Solides manualmente e redireciona.
     */
    public function syncSolides(Request $request)
    {
        $colaboradorId = $request->input('colaborador_id');

        if ($colaboradorId) {
            $colaborador = \App\Models\Colaborador::find($colaboradorId);
            if (!$colaborador || empty($colaborador->solides_id)) {
                return redirect()->back()->withErrors(['api' => 'Este colaborador não possui um ID da Sólides vinculado. Por favor, acesse o cadastro do colaborador e insira o ID antes de sincronizar.']);
            }
        }

        // Filtro Obrigatório do Mês (default para o atual se vazio)
        $mesAno = $request->input('mes_ano') ?: date('Y-m');
        
        $inicioMesCarbon = Carbon::parse($mesAno . '-01')->startOfMonth();
        $fimMesCarbon = Carbon::parse($mesAno . '-01')->endOfMonth();

        try {
            SolidesService::buscarEspelhoPonto($colaboradorId, $inicioMesCarbon, $fimMesCarbon);
            return redirect()->back()->with('success', 'Pontos sincronizados com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['api' => 'Erro ao sincronizar pontos: ' . $e->getMessage()]);
        }
    }

    /**
     * Sincronização global Sólides (chamada Ajax)
     */
    public function sincronizarTodos(Request $request)
    {
        // Aumenta o tempo limite para evitar timeout em bases grandes
        set_time_limit(300);

        try {
            $user = auth()->user();
            $colab = $user->colaborador;

            // Busca os colaboradores sob a mesma regra do index() para o usuário logado
            $query = \App\Models\Colaborador::ativos()
                // solides_id foi movido para users — filtramos via relacionamento
                ->whereHas('user', fn($q) => $q->whereNotNull('solides_id'))
                ->whereHas('setorRelacionamento', fn($s) => $s->where('ativo', true));

            if (!\App\Helpers\AcessoHelper::isOwner($user)) {
                if (\App\Helpers\AcessoHelper::isAdministrativo($user)) {
                    if (!$colab) {
                        $query->whereRaw('0=1');
                    } else {
                        $setoresGerenciados = $colab->setoresGerenciados()->pluck('setores.id');
                        if ($setoresGerenciados->isEmpty()) {
                            $query->where('id', $colab->id);
                        } else {
                            $query->where(function ($q) use ($setoresGerenciados, $colab) {
                                $q->whereIn('setor_id', $setoresGerenciados)
                                  ->orWhere('id', $colab->id);
                            });
                        }
                    }
                } else {
                    if (!$colab) {
                        $query->whereRaw('0=1');
                    } elseif (\App\Helpers\AcessoHelper::isAcessoExpandido($user)) {
                        $setoresVinculados = $colab->setoresVinculados()->pluck('setores.id');
                        $query->where(function ($q) use ($setoresVinculados, $colab) {
                            $q->whereIn('setor_id', $setoresVinculados)
                              ->orWhere('id', $colab->id);
                        });
                    } else {
                        $query->where('id', $colab->id);
                    }
                }
            }

            $colaboradores = $query->get();

            // Define o período
            $mesAno = $request->input('mes_ano') ?: date('Y-m');
            $dataInicio = Carbon::parse($mesAno . '-01')->startOfMonth();
            $dataFim = Carbon::now();

            foreach ($colaboradores as $c) {
                // Upsert cuida de não duplicar
                SolidesService::buscarEspelhoPonto($c->id, $dataInicio, $dataFim);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sincronização global concluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar pontos: ' . $e->getMessage()
            ], 500);
        }
    }
}
