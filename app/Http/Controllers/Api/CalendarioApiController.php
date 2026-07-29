<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\Feriado;
use App\Models\Notificacao;
use App\Services\ControlePontoService;
use App\Services\FeriadoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CalendarioApiController
 *
 * Equivalente à API Django:
 *   get_calendar_status_ajax(request) → status()
 *
 * GET /api/calendar-status?month=M&year=A
 *
 * Duas lógicas distintas (equivalente ao Django):
 *   1. Owner/Gestor → Visão global da empresa (todos os colaboradores)
 *   2. Colaborador  → Visão pessoal (apenas seus próprios apontamentos)
 *
 * Status possíveis por dia:
 *   future    → data futura
 *   day_off   → feriado ou fim de semana
 *   missing   → deveria trabalhar mas não registrou
 *   incomplete→ registrou mas abaixo da meta
 *   filled    → meta atingida
 */
class CalendarioApiController extends Controller
{
    /**
     * Status do calendário mensal.
     * Equivalente ao get_calendar_status_ajax() do Django (apis.py L241-403).
     *
     * GET /api/calendar-status?month=M&year=A
     */
    public function status(Request $request): JsonResponse
    {
        $month = (int) $request->query('month');
        $year  = (int) $request->query('year');

        if (!$month || !$year || $month < 1 || $month > 12) {
            return response()->json(['error' => 'Parâmetros inválidos'], 400);
        }

        $numDays   = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $today     = now()->toDateString();
        $user      = auth()->user();
        $daysData  = [];

        $startDate = Carbon::create($year, $month, 1)->toDateString();
        $endDate   = Carbon::create($year, $month, $numDays)->toDateString();

        // =====================================================================
        // 1. LÓGICA DO OWNER — Visão global da empresa
        // Mesmo critério do ConformidadeController: superuser OU admin
        // =====================================================================
        // MIGRADO: substituiu (is_superuser || nivel_acesso === 'ADMIN' || isOwner()) por isAdmin()
        $isAdmin = \App\Helpers\AcessoHelper::isAdmin($user);

        if ($isAdmin) {
            // Dias com alertas ALERTA notificados
            $diasNotificados = Notificacao::where('tipo', 'ALERTA')
                ->whereYear('data_referencia', $year)
                ->whereMonth('data_referencia', $month)
                ->pluck('data_referencia')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->flip() // Set para lookup O(1)
                ->all();

            // Todos os apontamentos do mês de todos os colaboradores
            $apontamentos = Apontamento::with('colaborador')
                ->whereBetween('data_apontamento', [$startDate, $endDate])
                ->get();

            // Base de Colaboradores Esperados (todos os perfis com escala de trabalho)
            $todosColaboradores = Colaborador::ativos()
                ->whereHas('setorRelacionamento', fn($q) => $q->where('ativo', true))
                ->get();
            $totalEsperado = $todosColaboradores->count();

            for ($day = 1; $day <= $numDays; $day++) {
                $currentDate = Carbon::create($year, $month, $day)->toDateString();

                if ($currentDate > $today) {
                    $daysData[] = ['day' => $day, 'date' => $currentDate, 'status' => 'future'];
                    continue;
                }

                $isFeriado  = FeriadoService::ehFeriado(Carbon::parse($currentDate));
                $isWeekend  = Carbon::parse($currentDate)->isWeekend();
                $statusDia  = ($isFeriado || $isWeekend) ? 'day_off' : 'missing';

                if (!($isFeriado || $isWeekend)) {
                    // Apontamentos do dia corrente
                    $aptsDia = $apontamentos->filter(
                        fn($a) => Carbon::parse($a->data_apontamento)->toDateString() === $currentDate
                    );

                    $colaboradoresQueEnviaram = $aptsDia->pluck('colaborador_id')->unique();
                    $totalEnviado = $colaboradoresQueEnviaram->intersect($todosColaboradores->pluck('id'))->count();

                    if ($totalEsperado > 0) {
                        if ($totalEnviado === 0) {
                            $statusDia = 'missing';
                        } elseif ($totalEnviado < $totalEsperado) {
                            $statusDia = 'incomplete';
                        } else {
                            $statusDia = 'filled';
                        }
                    } else {
                        $statusDia = 'filled';
                    }
                }

                $jaNotificado = isset($diasNotificados[$currentDate]);

                $daysData[] = [
                    'day'           => $day,
                    'date'          => $currentDate,
                    'status'        => $statusDia,
                    'is_owner'      => true,
                    'ja_notificado' => $jaNotificado,
                    'aviso_enviado' => $jaNotificado, // alias de compatibilidade
                ];
            }

            return response()->json(['is_owner' => true, 'days' => $daysData]);
        }

        // =====================================================================
        // 2. LÓGICA DO COLABORADOR — Visão pessoal
        // =====================================================================
        $colaborador = $user->colaborador;
        if (!$colaborador) {
            return response()->json(['error' => 'Colaborador não encontrado'], 400);
        }

        $mapaEscalas = ControlePontoService::obterEscalasDoMes(collect([$colaborador]), $month, $year);

        // Busca todos os apontamentos do colaborador no mês (equivalente ao .values() do Django)
        $registros = Apontamento::where('colaborador_id', $colaborador->id)
            ->whereBetween('data_apontamento', [$startDate, $endDate])
            ->get(['data_apontamento', 'hora_inicio', 'hora_termino', 'dorme_fora', 'em_plantao']);

        // Agrega totais por data (equivalente ao dado_dias{} do Django)
        $dadosDias  = [];
        $dummy = '2000-01-01';

        foreach ($registros as $entry) {
            $dStr = Carbon::parse($entry->data_apontamento)->toDateString();
            if (!isset($dadosDias[$dStr])) {
                $dadosDias[$dStr] = ['total_segundos' => 0, 'dorme_fora' => false, 'em_plantao' => false];
            }
            if ($entry->dorme_fora) {
                $dadosDias[$dStr]['dorme_fora'] = true;
            }
            if ($entry->em_plantao) {
                $dadosDias[$dStr]['em_plantao'] = true;
            }
            if ($entry->hora_inicio && $entry->hora_termino) {
                $ini = Carbon::parse("{$dummy} {$entry->hora_inicio}");
                $fim = Carbon::parse("{$dummy} {$entry->hora_termino}");
                if ($fim->lt($ini)) {
                    $fim->addDay();
                }
                $dadosDias[$dStr]['total_segundos'] += $ini->diffInSeconds($fim);
            }
        }

        for ($day = 1; $day <= $numDays; $day++) {
            $currentDate   = Carbon::create($year, $month, $day)->toDateString();
            $status        = 'missing';
            $hasDormeFora  = false;
            $hasEmPlantao  = false;

            if ($currentDate > $today) {
                $status = 'future';
            } else {
                $dadosPonto = $mapaEscalas[$colaborador->id][Carbon::parse($currentDate)->toDate()] ?? null;
                if (!$dadosPonto) {
                    $dadosPonto = ['meta_segundos' => 31680, 'tolerancia_segundos' => 600];
                }

                $meta     = $dadosPonto['meta_segundos'];
                $tol      = $dadosPonto['tolerancia_segundos'];
                $realizado = 0;

                if (isset($dadosDias[$currentDate])) {
                    $realizado    = $dadosDias[$currentDate]['total_segundos'];
                    $hasDormeFora = $dadosDias[$currentDate]['dorme_fora'];
                    $hasEmPlantao = $dadosDias[$currentDate]['em_plantao'];
                }

                if ($meta === 0) {
                    $status = $realizado > 0 ? 'filled' : 'day_off';
                } else {
                    if ($realizado === 0) {
                        $status = 'missing';
                    } elseif ($realizado < ($meta - $tol)) {
                        $status = 'incomplete';
                    } else {
                        $status = 'filled';
                    }
                }
            }

            $daysData[] = [
                'date'           => $currentDate,
                'day'            => $day,
                'status'         => $status,
                'has_dorme_fora' => $hasDormeFora,
                'has_em_plantao' => $hasEmPlantao,
                'is_owner'       => false,
            ];
        }

        return response()->json(['is_owner' => false, 'days' => $daysData]);
    }
}
