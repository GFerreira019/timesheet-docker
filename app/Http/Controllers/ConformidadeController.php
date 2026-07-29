<?php

namespace App\Http\Controllers;

use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\Feriado;
use App\Models\Notificacao;
use App\Services\AuditoriaService;
use App\Services\ControlePontoService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ConformidadeController
 *
 * Equivalente às views Django:
 *   dashboard_conformidade_view()     → dashboard()
 *   notificar_pendencias_view()       → notificarPendencias()
 *   enviar_aviso_personalizado_view() → enviarAvisoPersonalizado()
 *   painel_owner_view()               → painelOwner()
 *
 * Requer is_owner (middleware 'owner').
 */
class ConformidadeController extends Controller
{
    /**
     * Dashboard de monitoramento de conformidade do dia.
     * Equivalente ao dashboard_conformidade_view() do Django.
     *
     * GET /conformidade/dashboard
     */
    public function dashboard(Request $request): View
    {
        $dataStr = $request->query('data');

        try {
            $dataRef = $dataStr ? Carbon::parse($dataStr)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            $dataRef = now()->toDateString();
        }

        $dataCarbon     = Carbon::parse($dataRef);
        $isFimDeSemana  = $dataCarbon->isWeekend();
        $feriadoObj     = Feriado::where('data', $dataRef)->first();
        $nomeFeriado    = $feriadoObj?->descricao;
        $isFeriado      = (bool) $feriadoObj;

        $user = auth()->user();
        // MIGRADO: substituiu nivel_acesso === 'ADMIN' || isOwner() por isAdmin() (cobre ambos via Spatie)
        abort_if(!AcessoHelper::isAdmin($user), 403, 'Acesso restrito a administradores.');

        // Colaboradores operacionais (exclui ADMIN e GERENCIAL que são pessoais)
        $colaboradores = Colaborador::ativos()
            ->whereHas('setorRelacionamento', fn($q) => $q->where('ativo', true))
            ->orderBy('nome_completo')
            ->get();

        // Obtém escala do mês via ControlePontoService (equivalente ao Django)
        $mes          = $dataCarbon->month;
        $ano          = $dataCarbon->year;
        $mapaEscalas  = ControlePontoService::obterEscalasDoMes($colaboradores, $mes, $ano);

        $listaOk                    = [];
        $listaIncompleto            = [];
        $listaAusenteSemApontamento = [];
        $listaAusenteReal           = [];

        foreach ($colaboradores as $colab) {
            $pendenteSolides = empty($colab->solides_id);
            $totalSegundosSolides = 0;
            $qtdRegistrosSolides  = 0;

            $segundosAbonados = 0;
            $diaTrabalhado = true;
            $justificativaAbono = null;
            
            if (!$pendenteSolides) {
                // Cálculo Real do Saldo usando SolidesPonto
                $pontosSolides = \App\Models\SolidesPonto::where('colaborador_id', $colab->id)
                    ->whereDate('data', $dataRef)
                    ->get();
                
                foreach ($pontosSolides as $ps) {
                    if ($ps->is_ajustado) {
                        if (!$ps->dia_trabalhado) {
                            $diaTrabalhado = false;
                        }
                        if ($ps->justificativa) {
                            $justificativaAbono = $ps->justificativa;
                        }
                        if ($ps->horas_abonadas) {
                            $horasDecimal = (float) $ps->horas_abonadas;
                            if ($horasDecimal > 100) $horasDecimal = $horasDecimal / 1000;
                            $segundosAbonados += (int) ($horasDecimal * 3600);
                        }
                    } elseif ($ps->hora_entrada && $ps->hora_saida) {
                        $entrada = Carbon::parse($ps->hora_entrada);
                        $saida = Carbon::parse($ps->hora_saida);
                        // Tratar virada de noite (se saída for menor que entrada)
                        if ($saida->lt($entrada)) {
                            $saida->addDay();
                        }
                        $totalSegundosSolides += $entrada->diffInSeconds($saida);
                        $qtdRegistrosSolides++;
                    }
                }
            }

            // Cálculo Real do Saldo usando Timesheet (Apontamentos)
            $apontamentos = \App\Models\Apontamento::where('colaborador_id', $colab->id)
                ->whereDate('data_apontamento', $dataRef)
                ->get();
                
            $totalSegundosTimesheet = 0;
            $qtdApontamentos = 0;
            foreach ($apontamentos as $ap) {
                if ($ap->hora_inicio && $ap->hora_termino) {
                    $inicio = Carbon::parse($ap->hora_inicio);
                    $fim = Carbon::parse($ap->hora_termino);
                    if ($fim->lt($inicio)) {
                        $fim->addDay();
                    }
                    $totalSegundosTimesheet += $inicio->diffInSeconds($fim);
                    $qtdApontamentos++;
                }
            }

            // Dados de ponto (escala teórica do mês)
            $dadosPonto = $mapaEscalas[$colab->id][$dataRef] ?? null;

            if (!$dadosPonto) {
                $dadosPonto = [
                    'deve_notificar'      => !$isFeriado && !$isFimDeSemana,
                    'meta_segundos'       => 31680,
                    'tolerancia_segundos' => 600,
                ];
            }

            $deveNotificar = $dadosPonto['deve_notificar'] ?? true;
            if (!$deveNotificar || !$diaTrabalhado) {
                $metaSegundos = 0;
                $tolerancia   = 0;
            } else {
                $metaSegundos = $dadosPonto['meta_segundos'] ?? 31680;
                $tolerancia   = $dadosPonto['tolerancia_segundos'] ?? 600;
                
                if ($segundosAbonados > 0) {
                    $metaSegundos -= $segundosAbonados;
                    if ($metaSegundos < 0) $metaSegundos = 0;
                }
            }

            // Pula dias de folga sem apontamentos e sem Sólides real
            // MAS NÃO PULA se tiver um atestado/abono, para que apareça na lista de Ausente (Real) com a justificativa
            if ($metaSegundos === 0 && $totalSegundosSolides === 0 && $totalSegundosTimesheet === 0 && !$justificativaAbono) {
                continue;
            }

            $horasSolides = (int) ($totalSegundosSolides / 3600);
            $minSolides   = (int) (($totalSegundosSolides % 3600) / 60);
            $totalHorasSolides = sprintf('%02d:%02d', $horasSolides, $minSolides);

            $horasTimesheet = (int) ($totalSegundosTimesheet / 3600);
            $minTimesheet   = (int) (($totalSegundosTimesheet % 3600) / 60);
            $totalHorasTimesheet = sprintf('%02d:%02d', $horasTimesheet, $minTimesheet);

            $diff = $totalSegundosSolides - $totalSegundosTimesheet;
            $saldoHorasStr = '00:00';
            
            if ($diff > 0) {
                $hDef = (int) ($diff / 3600);
                $mDef = (int) (($diff % 3600) / 60);
                $saldoHorasStr = sprintf('Faltam %02d:%02d', $hDef, $mDef);
            } elseif ($diff < 0) {
                $absDiff = abs($diff);
                $hSup = (int) ($absDiff / 3600);
                $mSup = (int) (($absDiff % 3600) / 60);
                $saldoHorasStr = sprintf('Extra %02d:%02d', $hSup, $mSup);
            }

            $dadosColab = [
                'nome'                 => $colab->nome_completo,
                'cargo'                => $colab->cargo,
                'qtd_registros'        => $qtdRegistrosSolides,
                'qtd_apontamentos'     => $qtdApontamentos,
                'total_horas_solides'  => $totalHorasSolides,
                'total_horas_timesheet'=> $totalHorasTimesheet,
                'saldo_horas'          => $saldoHorasStr,
                'pendente_solides'     => $pendenteSolides,
                'justificativa_abono'  => $justificativaAbono,
            ];

            // Classificação
            if ($qtdApontamentos === 0) {
                // Não enviou timesheet
                if ($qtdRegistrosSolides > 0) {
                    $listaAusenteSemApontamento[] = $dadosColab;
                } else {
                    $listaAusenteReal[] = $dadosColab;
                }
            } elseif (abs($diff) > $tolerancia) {
                // Tem diferença fora da tolerância (Divergente)
                $listaIncompleto[] = $dadosColab;
            } else {
                // Tudo ok
                $listaOk[] = $dadosColab;
            }
        }

        $totalPertinentes = count($listaOk) + count($listaIncompleto) + count($listaAusenteSemApontamento);
        
        $qtdEnviadosCorretamente = count($listaOk);
        $qtdDivergentes = count($listaIncompleto);
        
        $pontuacao = ($qtdEnviadosCorretamente * 1) + ($qtdDivergentes * 0.75);

        $percentualAdesao = $totalPertinentes > 0
            ? round(($pontuacao / $totalPertinentes) * 100, 1)
            : 0;

        return view('conformidade.dashboard', [
            'titulo'              => 'Monitoramento de Conformidade',
            'is_owner'            => true,
            'data_ref'            => $dataRef,
            'data_ref_str'        => $dataRef,
            'next_date'           => $dataCarbon->addDay()->toDateString(),
            'prev_date'           => Carbon::parse($dataRef)->subDay()->toDateString(),
            'lista_ok'            => $listaOk,
            'lista_incompleto'    => $listaIncompleto,
            'lista_ausente'       => $listaAusenteSemApontamento,
            'lista_ausente_real'  => $listaAusenteReal,
            'total_colaboradores' => $totalPertinentes,
            'percentual_adesao'   => $percentualAdesao,
            'nome_feriado'        => $nomeFeriado,
            'is_feriado'          => $isFeriado || $isFimDeSemana,
            'colaboradores'       => $colaboradores,
        ]);
    }

    /**
     * Dispara notificações em massa para colaboradores com pendência.
     * Equivalente ao notificar_pendencias_view() do Django.
     *
     * POST /conformidade/notificar-pendencias
     */
    public function notificarPendencias(Request $request): RedirectResponse
    {
        $dataStr = $request->input('data_ref');

        try {
            $dataRef = Carbon::parse($dataStr)->toDateString();
        } catch (\Throwable) {
            session()->flash('error', 'Data inválida para notificação.');
            return redirect()->route('conformidade.dashboard');
        }

        $colaboradores = Colaborador::ativos()
            ->whereHas('setorRelacionamento', fn($q) => $q->where('ativo', true))
            ->get();

        $colaboradoresParaNotificar = [];
        $notificacoesCriar = [];
        $countCriadas      = 0;

        foreach ($colaboradores as $colab) {
            if (!$colab->user_id) {
                continue;
            }

            // A) Já preencheu timesheet?
            $temApontamento = Apontamento::where('colaborador_id', $colab->id)
                ->where('data_apontamento', $dataRef)
                ->exists();

            \Illuminate\Support\Facades\Log::info("Triagem - {$colab->nome_completo} | Tem apontamento? " . ($temApontamento ? 'SIM' : 'NAO'));

            if ($temApontamento) {
                continue;
            }

            // B) Verifica Solides
            $bateuPonto = false;
            try {
                $espelho = \App\Services\SolidesService::buscarEspelhoPonto($colab->id, $dataRef, $dataRef);
                $registros = $espelho['registros'] ?? [];
                
                foreach ($registros as $reg) {
                    if ($reg['data'] === $dataRef) {
                        if (!empty($reg['t1_inicio']) && $reg['t1_inicio'] !== '-') {
                            $bateuPonto = true;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Triagem - Erro Sólides - {$colab->nome_completo} | Erro: " . $e->getMessage());
            }

            \Illuminate\Support\Facades\Log::info("Triagem - {$colab->nome_completo} | Bateu Ponto Sólides? " . ($bateuPonto ? 'SIM' : 'NAO'));

            // C) Não bateu ponto
            if (!$bateuPonto) {
                continue;
            }

            // D) Bateu ponto, adiciona
            $colaboradoresParaNotificar[] = $colab;

            $dataFmt    = Carbon::parse($dataRef)->format('d/m');
            $primeiroNome = explode(' ', $colab->nome_completo)[0];

            $notificacoesCriar[] = new Notificacao([
                'colaborador_id' => $colab->id,
                'titulo'         => 'Ausência de Registro',
                'mensagem'       => "Olá {$primeiroNome}, não identificamos apontamentos seus no dia {$dataFmt}. Por favor, verifique.",
                'tipo'           => 'ALERTA',
                'data_referencia'=> $dataRef,
                'remetente_id'   => auth()->id(),
            ]);
            $countCriadas++;
        }

        if (empty($colaboradoresParaNotificar)) {
            session()->flash('info', 'Nenhuma pendência encontrada para notificar neste dia (Dia ok ou folga/feriado).');
            return redirect()->route('conformidade.dashboard', ['data' => $dataStr]);
        }
        if (!empty($notificacoesCriar)) {
            foreach ($notificacoesCriar as $novaNotificacao) {
                $novaNotificacao->save();
            }

            // Dispara WhatsApp para cada alerta (equivalente ao Django)
            $wppEnviados = 0;
            $falhasWpp = [];

            foreach ($notificacoesCriar as $notif) {
                if ($notif->tipo === 'ALERTA') {
                    $colab = Colaborador::find($notif->colaborador_id);
                    if (!$colab) {
                        continue;
                    }
                    $primeiroNome = explode(' ', $colab->nome_completo)[0];
                    $dataFmtWpp   = Carbon::parse($dataRef)->format('d/m/Y');
                    $msgWpp = "*⚠️ Atenção*\n\nOlá {$primeiroNome},\nHá notificações no seu Connect-Timesheet referentes ao dia {$dataFmtWpp}.\nPor favor, acesse o sistema para verificar.";

                    try {
                        if (WhatsAppService::enviarNotificacaoPendencia($colab, $msgWpp)) {
                            $wppEnviados++;
                        }
                    } catch (\Exception $e) {
                        $falhasWpp[] = [
                            'nome'     => $colab->nome_completo,
                            'erro'     => $e->getMessage(),
                            'mensagem' => $msgWpp,
                        ];
                    }
                }
            }

            session()->flash('success', "Sucesso! {$countCriadas} notificações foram salvas. WhatsApp enviado para {$wppEnviados} colaboradores.");
            
            if (!empty($falhasWpp)) {
                session()->flash('falhas_wpp', $falhasWpp);
            }
        }

        return redirect()->route('conformidade.dashboard', ['data' => $dataStr]);
    }

    /**
     * Envia aviso manual para um colaborador específico.
     * Equivalente ao enviar_aviso_personalizado_view() do Django.
     *
     * POST /conformidade/enviar-aviso
     */
    public function enviarAvisoPersonalizado(Request $request): RedirectResponse
    {
        $colaborId  = $request->input('colaborador_id');
        $titulo     = $request->input('titulo');
        $msg        = $request->input('mensagem');
        $dataRefStr = $request->input('data_referencia');

        if (!$colaborId || !$titulo || !$msg) {
            session()->flash('error', 'Preencha todos os campos.');
            return redirect()->route('conformidade.dashboard');
        }

        $colab = Colaborador::findOrFail($colaborId);

        $dataFinal = now()->toDateString();
        if ($dataRefStr) {
            try {
                $dataFinal = Carbon::parse($dataRefStr)->toDateString();
            } catch (\Throwable) {}
        }

        $dataFormatadaMsg = Carbon::parse($dataFinal)->format('d/m/Y');

        $notificacao = Notificacao::create([
            'colaborador_id' => $colab->id,
            'titulo'         => $titulo,
            'mensagem'       => $msg,
            'tipo'           => 'INFO',
            'data_referencia'=> $dataFinal,
            'remetente_id'   => auth()->id(),
        ]);

        $primeiroNome = explode(' ', $colab->nome_completo)[0];
        $msgWpp = "*⚠️ Atenção*\n\nOlá {$primeiroNome},\nHá notificações no seu Connect-Timesheet referentes ao dia {$dataFormatadaMsg}.\nPor favor, acesse o sistema para verificar.";

        $sucesso      = WhatsAppService::enviarNotificacaoPendencia($colab, $msgWpp);
        $statusEnvio  = $sucesso ? 'WhatsApp enviado.' : 'Falha ao enviar WhatsApp.';

        AuditoriaService::registrar(
            $request,
            'CRIACAO',
            'Notificacao',
            $notificacao->id,
            "Aviso manual disparado para: {$colab->nome_completo}. Título: '{$titulo}'. Ref: {$dataFormatadaMsg}. Status WhatsApp: {$statusEnvio}."
        );

        if ($sucesso) {
            session()->flash('success', "Mensagem enviada para {$colab->nome_completo} via WhatsApp. (Ref: {$dataFormatadaMsg})");
        } else {
            session()->flash('warning', "Mensagem enviada para {$colab->nome_completo} apenas pelo sistema, falha via WhatsApp.");
        }

        return redirect()->route('conformidade.dashboard');
    }

    /**
     * Sincroniza pontos dos últimos 7 dias na Sólides
     *
     * POST /conformidade/sincronizar-solides
     */
    public function sincronizarSolides(Request $request): \Illuminate\Http\JsonResponse
    {
        $colaboradores = Colaborador::ativos()
            ->whereHas('setorRelacionamento', fn($q) => $q->where('ativo', true))
            ->whereNotNull('solides_id')
            ->get();

        $dataInicio = now()->subDays(7)->format('Y-m-d');
        $dataFim = now()->format('Y-m-d');

        foreach ($colaboradores as $colab) {
            try {
                \App\Services\SolidesService::buscarEspelhoPonto($colab->id, $dataInicio, $dataFim);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erro na sinc. Sólides (7 dias) para Colaborador {$colab->id}: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Sincronização de 7 dias finalizada com sucesso!']);
    }

    /**
     * Hub central do Owner.
     * Equivalente ao painel_owner_view() do Django.
     *
     * GET /owner/painel
     */
    public function painelOwner(): View
    {
        return view('owner.painel', [
            'titulo' => 'Painel Administrativo',
        ]);
    }
}
