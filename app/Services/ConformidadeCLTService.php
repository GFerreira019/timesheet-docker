<?php

namespace App\Services;

use App\Models\Apontamento;
use App\Models\Colaborador;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ConformidadeCLTService
 *
 * Equivalente à função calcular_regras_clt() do utils.py do Django.
 * Motor das 3 regras de conformidade CLT do sistema.
 *
 * CONCEITO CENTRAL — "Data Contábil":
 *   O dia de trabalho começa às 06:00 e vai até 05:59 do dia seguinte.
 *   Para verificar violações de interjornada e jornada diária,
 *   o sistema analisa sempre 3 dias consecutivos (D-1, D, D+1).
 *   Equivalente exato ao get_data_contabil() e calcular_regras_clt() do Django.
 *
 * As 3 Regras CLT verificadas:
 *   1. LIMITE DIÁRIO   → máximo de 10h48min (38880 segundos) de trabalho por dia contábil
 *   2. INTRAJORNADA    → máximo de 6h contínuas sem descanso (verificação simplificada)
 *   3. INTERJORNADA    → mínimo de 11h (39600 segundos) de descanso entre jornadas
 */
class ConformidadeCLTService
{
    // -------------------------------------------------------------------------
    // Constantes CLT (equivalentes às constantes do Django utils.py)
    // -------------------------------------------------------------------------

    /** 10h48m em segundos = 10 * 3600 + 48 * 60 = 38880 */
    public const LIMITE_DIARIO_SEGUNDOS = 38880;

    /** 11h em segundos = 11 * 3600 = 39600 */
    public const INTERJORNADA_MINIMA_SEGUNDOS = 39600;

    /** 6h em segundos = 6 * 3600 = 21600 */
    public const INTRAJORNADA_MAXIMA_SEGUNDOS = 21600;

    /** Hora de corte da "Data Contábil": 06:00 */
    public const HORA_INICIO_CONTABIL = 6;

    // -------------------------------------------------------------------------
    // API Pública
    // -------------------------------------------------------------------------

    /**
     * Calcula e persiste os flags CLT para um colaborador em uma data contábil.
     *
     * Equivalente exato à função calcular_regras_clt(colaborador, data_contabil) do Django.
     * Analisa os apontamentos de 3 dias (D-1, D, D+1) para detectar violações.
     *
     * @param Colaborador   $colaborador  Colaborador alvo
     * @param Carbon|string $dataContabil Data contábil de referência (dia central)
     */
    public static function calcularRegrasClt(Colaborador $colaborador, Carbon|string $dataContabil): void
    {
        $diaRef = $dataContabil instanceof Carbon
            ? $dataContabil->clone()->startOfDay()
            : Carbon::parse($dataContabil)->startOfDay();

        // Coletar apontamentos dos 3 dias para análise de interjornada
        $diasParaAnalise = [
            $diaRef->clone()->subDay(),
            $diaRef->clone(),
            $diaRef->clone()->addDay(),
        ];

        $apontamentosPorDia = [];

        foreach ($diasParaAnalise as $dia) {
            $apontamentosPorDia[$dia->toDateString()] = Apontamento::where('colaborador_id', $colaborador->id)
                ->whereDate('data_apontamento', $dia->toDateString())
                ->whereNotNull('hora_termino')  // ignora check-in em aberto
                ->orderBy('hora_inicio')
                ->get();
        }

        // -----------------------------------------------------------------------
        // Coletar todos os segmentos de tempo do dia central (para as 3 regras)
        // -----------------------------------------------------------------------
        $segmentosDoDia = self::extrairSegmentos(
            $apontamentosPorDia[$diaRef->toDateString()]
        );

        // -----------------------------------------------------------------------
        // REGRA 1: Limite Diário (10h48min)
        // -----------------------------------------------------------------------
        $totalSegundosDia = array_sum(array_column($segmentosDoDia, 'duracao'));
        $violaLimiteDiario = $totalSegundosDia > self::LIMITE_DIARIO_SEGUNDOS;

        // -----------------------------------------------------------------------
        // REGRA 2: Intrajornada — verificação de blocos contínuos de 6h+
        // -----------------------------------------------------------------------
        $violaIntrajornada = false;
        $blocoContinuo     = 0;
        foreach ($segmentosDoDia as $i => $seg) {
            if ($i === 0) {
                $blocoContinuo = $seg['duracao'];
                continue;
            }

            $gapAnterior = $segmentosDoDia[$i]['inicio'] - $segmentosDoDia[$i - 1]['fim'];

            if ($gapAnterior <= 0) {
                // Sem gap — blocos contíguos
                $blocoContinuo += $seg['duracao'];
            } else {
                // Reset do bloco ao detectar qualquer descanso
                $blocoContinuo = $seg['duracao'];
            }

            if ($blocoContinuo > self::INTRAJORNADA_MAXIMA_SEGUNDOS) {
                $violaIntrajornada = true;
                break;
            }
        }

        // -----------------------------------------------------------------------
        // REGRA 3: Interjornada (11h de descanso entre jornadas)
        // -----------------------------------------------------------------------
        $violaInterAnterior = false;
        $violaInterSeguinte = false;

        // Último registro do dia anterior
        $segDiaAnterior = self::extrairSegmentos(
            $apontamentosPorDia[$diaRef->clone()->subDay()->toDateString()]
        );
        $segDiaSeguinte = self::extrairSegmentos(
            $apontamentosPorDia[$diaRef->clone()->addDay()->toDateString()]
        );

        if (!empty($segDiaAnterior) && !empty($segmentosDoDia)) {
            // Gap entre fim do dia anterior e início do dia atual
            // Normaliza para timestamps absolutos
            $fimAnterior  = $segDiaAnterior[count($segDiaAnterior) - 1]['fim_abs'];
            $inicioAtual  = $segmentosDoDia[0]['inicio_abs'];
            $gapHoras     = ($inicioAtual - $fimAnterior);

            if ($gapHoras < self::INTERJORNADA_MINIMA_SEGUNDOS && $gapHoras > 0) {
                $violaInterAnterior = true;
            }
        }

        if (!empty($segmentosDoDia) && !empty($segDiaSeguinte)) {
            // Gap entre fim do dia atual e início do dia seguinte
            $fimAtual       = $segmentosDoDia[count($segmentosDoDia) - 1]['fim_abs'];
            $inicioSeguinte = $segDiaSeguinte[0]['inicio_abs'];
            $gapHoras       = ($inicioSeguinte - $fimAtual);

            if ($gapHoras < self::INTERJORNADA_MINIMA_SEGUNDOS && $gapHoras > 0) {
                $violaInterSeguinte = true;
            }
        }

        // -----------------------------------------------------------------------
        // Construir flag e motivo (equivalente ao Django)
        // -----------------------------------------------------------------------
        $flagAtencao  = false;
        $motivoAlerta = null;
        $motivoPartes = [];

        if ($violaLimiteDiario) {
            $flagAtencao    = true;
            $totalHm        = self::formatarDuracao($totalSegundosDia);
            $motivoPartes[] = "LIMITE DIÁRIO: {$totalHm} trabalhados (máx. 10:48)";
        }

        if ($violaIntrajornada) {
            $flagAtencao    = true;
            $motivoPartes[] = 'INTRAJORNADA: Possível bloco contínuo superior a 6h sem descanso.';
        }

        if ($violaInterAnterior || $violaInterSeguinte) {
            $flagAtencao    = true;
            $motivoPartes[] = 'INTERJORNADA: Descanso mínimo de 11h entre jornadas não respeitado.';
        }

        if ($flagAtencao) {
            $motivoAlerta = implode(' | ', $motivoPartes);
        }

        // -----------------------------------------------------------------------
        // REGRAS DE NEGÓCIO E PERSISTÊNCIA EM LOTE
        // 1. Limite e Intrajornada: Afetam TODOS os apontamentos do diaRef.
        // 2. Bloqueio: Se houver $flagAtencao, o status é revertido para EM_ANALISE.
        // -----------------------------------------------------------------------
        $apontamentosDia = Apontamento::where('colaborador_id', $colaborador->id)
            ->whereDate('data_apontamento', $diaRef->toDateString())
            ->get();
            
        foreach ($apontamentosDia as $ap) {
            $ap->flag_atencao = $flagAtencao;
            $ap->motivo_alerta = $motivoAlerta;
            
            if ($flagAtencao) {
                // Trava o apontamento. Ele jamais será aprovado pelo sistema automático
                $ap->status_aprovacao = 'EM_ANALISE';
            }
            $ap->save();
        }

        // -----------------------------------------------------------------------
        // TRAVA DUPLA DE INTERJORNADA (A regra mais estrita)
        // Bloqueia e alerta AMBOS os dias envolvidos na infração de descanso.
        // -----------------------------------------------------------------------
        if ($violaInterAnterior) {
            self::bloquearDiaPorInterjornada($colaborador->id, $diaRef->clone()->subDay()->toDateString());
        }

        if ($violaInterSeguinte) {
            self::bloquearDiaPorInterjornada($colaborador->id, $diaRef->clone()->addDay()->toDateString());
        }
    }

    /**
     * Retorna a "Data Contábil" de um horário.
     *
     * Equivalente exato à função get_data_contabil(hora_inicio) do Django utils.py:
     *   - Horário >= 06:00 → data do próprio dia
     *   - Horário < 06:00  → considera data do dia anterior (turno noturno)
     *
     * @param Carbon $horaInicio
     * @return Carbon Data contábil (apenas a data, sem horário)
     */
    public static function getDataContabil(Carbon $horaInicio): Carbon
    {
        if ($horaInicio->hour < self::HORA_INICIO_CONTABIL) {
            // Entre 00:00 e 05:59 → pertence ao dia anterior
            return $horaInicio->clone()->subDay()->startOfDay();
        }

        // 06:00 em diante → pertence ao próprio dia
        return $horaInicio->clone()->startOfDay();
    }

    // -------------------------------------------------------------------------
    // Helpers Privados
    // -------------------------------------------------------------------------

    /**
     * Extrai segmentos de tempo de uma coleção de apontamentos.
     * Retorna array de ['inicio', 'fim', 'duracao', 'inicio_abs', 'fim_abs']
     * onde inicio/fim são segundos dentro de um dia de 48h (para overnight).
     *
     * @param  \Illuminate\Database\Eloquent\Collection $apontamentos
     * @return array
     */
    private static function extrairSegmentos($apontamentos): array
    {
        $segmentos = [];

        foreach ($apontamentos as $apt) {
            if (!$apt->hora_inicio || !$apt->hora_termino) {
                continue;
            }

            [$hiH, $hiM, $hiS] = explode(':', substr($apt->hora_inicio, 0, 8) . ':00');
            [$htH, $htM, $htS] = explode(':', substr($apt->hora_termino, 0, 8) . ':00');

            $inicioSeg = (int)$hiH * 3600 + (int)$hiM * 60 + (int)$hiS;
            $fimSeg    = (int)$htH * 3600 + (int)$htM * 60 + (int)$htS;

            // Trata virada de meia-noite (overnight)
            if ($fimSeg < $inicioSeg) {
                $fimSeg += 86400;
            }

            // Timestamps absolutos para cálculo de interjornada entre dias
            $dataBase = Carbon::parse($apt->data_apontamento)->timestamp;

            $segmentos[] = [
                'inicio'     => $inicioSeg,
                'fim'        => $fimSeg,
                'duracao'    => $fimSeg - $inicioSeg,
                'inicio_abs' => $dataBase + $inicioSeg,
                'fim_abs'    => $dataBase + $fimSeg,
            ];
        }

        // Ordenar por horário de início
        usort($segmentos, fn($a, $b) => $a['inicio'] <=> $b['inicio']);

        return $segmentos;
    }

    /**
     * Aplica o bloqueio e alerta de interjornada para um dia adjacente.
     * Auxilia a regra de Trava Dupla.
     */
    private static function bloquearDiaPorInterjornada($colaboradorId, string $dataBloqueio): void
    {
        $apontamentos = Apontamento::where('colaborador_id', $colaboradorId)
            ->whereDate('data_apontamento', $dataBloqueio)
            ->get();
            
        $msg = 'INTERJORNADA: Descanso mínimo de 11h entre jornadas não respeitado.';
        
        foreach ($apontamentos as $ap) {
            $motivos = $ap->motivo_alerta ? explode(' | ', $ap->motivo_alerta) : [];
            if (!in_array($msg, $motivos)) {
                $motivos[] = $msg;
            }
            
            $ap->flag_atencao = true;
            $ap->motivo_alerta = implode(' | ', $motivos);
            
            // Força a voltar para análise, barrando qualquer aprovação prévia
            $ap->status_aprovacao = 'EM_ANALISE'; 
            $ap->save();
        }
    }

    /**
     * Formata segundos em string "HH:MM".
     * Equivalente ao _formatar_duracao do utils.py.
     */
    private static function formatarDuracao(int $segundos): string
    {
        $h = floor($segundos / 3600);
        $m = floor(($segundos % 3600) / 60);
        return sprintf('%02d:%02d', $h, $m);
    }
}
