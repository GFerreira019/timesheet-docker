<?php

namespace App\Services;

use App\Helpers\AcessoHelper;
use App\Models\Colaborador;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * ControlePontoService
 *
 * Equivalente à classe ControlePontoService do services.py do Django.
 * Responsável por consultar a meta diária de trabalho de cada colaborador.
 *
 * Regra de negócio central:
 * - Dia útil    → meta 08:48h (31680 segundos) com tolerância de 15 min (900s)
 * - Fim de semana ou Feriado → meta 0 (não notifica)
 * - Cargo isento (GERENTE, DIRETOR, etc.) → meta 0 (não notifica)
 *
 * Preparado para integração futura com API Sólides (obterEscalasDoMes).
 */
class ControlePontoService
{
    // -------------------------------------------------------------------------
    // Constantes (equivalentes às class-level constants do Django)
    // -------------------------------------------------------------------------

    /** 08h48m em segundos (8*3600 + 48*60) — equivalente ao META_PADRAO do Django */
    public const META_PADRAO = 31680;

    /** 15 minutos em segundos — equivalente ao TOLERANCIA_PADRAO do Django */
    public const TOLERANCIA_PADRAO = 900;

    // -------------------------------------------------------------------------
    // Métodos Públicos
    // -------------------------------------------------------------------------

    /**
     * Retorna a meta de trabalho para um colaborador em um dia específico.
     *
     * Equivalente exato ao método Django:
     *   ControlePontoService.obter_meta_do_dia(colaborador, data_ref)
     *
     * @return array{meta_segundos: int, tolerancia_segundos: int, deve_notificar: bool, motivo_ausencia: string|null}
     */
    public static function obterMetaDoDia(Colaborador $colaborador, Carbon $data): array
    {
        $cargo = strtoupper($colaborador->cargo ?? '');

        // Verificar se o cargo é isento de meta
        if (AcessoHelper::isCargoIsento($cargo)) {
            return [
                'meta_segundos'      => 0,
                'tolerancia_segundos'=> 0,
                'deve_notificar'     => false,
                'motivo_ausencia'    => 'Cargo Isento',
            ];
        }

        // Fallback local (sem API Sólides ainda)
        return self::calcularMetaPadrao($data, $colaborador->cidade, $colaborador->uf);
    }

    /**
     * Busca em lote (batch) a escala de múltiplos colaboradores para um mês inteiro.
     *
     * Equivalente ao método Django:
     *   ControlePontoService.obter_escalas_do_mes(colaboradores, mes, ano)
     *
     * @param  \Illuminate\Support\Collection $colaboradores  Coleção de Colaborador
     * @param  int $mes  Mês (1-12)
     * @param  int $ano  Ano (ex: 2025)
     * @return array<int, array<string, array>>  [colaborador_id => [data_str => meta_array]]
     */
    public static function obterEscalasDoMes(iterable $colaboradores, int $mes, int $ano): array
    {
        $mapaEscalas   = [];
        $numDias       = Carbon::create($ano, $mes, 1)->daysInMonth;

        $idsParaConsultar = [];

        foreach ($colaboradores as $colab) {
            $mapaEscalas[$colab->id] = [];
            $cargo = strtoupper($colab->cargo ?? '');

            if (AcessoHelper::isCargoIsento($cargo)) {
                // Cargo isento — preenche todos os dias com meta 0
                for ($dia = 1; $dia <= $numDias; $dia++) {
                    $data = Carbon::create($ano, $mes, $dia);
                    $mapaEscalas[$colab->id][$data->toDateString()] = [
                        'meta_segundos'      => 0,
                        'tolerancia_segundos'=> 0,
                        'deve_notificar'     => false,
                        'motivo_ausencia'    => 'Cargo Isento',
                    ];
                }
            } else {
                $idsParaConsultar[] = $colab->id;
            }
        }

        if (empty($idsParaConsultar)) {
            return $mapaEscalas;
        }

        // -----------------------------------------------------------------------
        // INTEGRAÇÃO FUTURA COM A API SÓLIDES
        // -----------------------------------------------------------------------
        // $payload = ['mes' => $mes, 'ano' => $ano, 'colaboradores' => $idsParaConsultar];
        // $dadosApi = Http::post(config('services.solides.url') . '/escalas/lote', $payload)->json();
        $dadosApi = null;

        // -----------------------------------------------------------------------
        // FALLBACK LOCAL (equivalente ao Django)
        // -----------------------------------------------------------------------
        foreach ($colaboradores as $colab) {
            if (!in_array($colab->id, $idsParaConsultar, true)) {
                continue;
            }

            for ($dia = 1; $dia <= $numDias; $dia++) {
                $data = Carbon::create($ano, $mes, $dia);

                if ($dadosApi && isset($dadosApi[(string) $colab->id])) {
                    // [Extrair dados da API Sólides aqui futuramente]
                } else {
                    $mapaEscalas[$colab->id][$data->toDateString()] = self::calcularMetaPadrao(
                        $data,
                        $colab->cidade,
                        $colab->uf
                    );
                }
            }
        }

        return $mapaEscalas;
    }

    // -------------------------------------------------------------------------
    // Método Privado (Fallback Local)
    // -------------------------------------------------------------------------

    /**
     * Calcula a meta padrão com base em dia útil, fim de semana e feriado.
     *
     * Equivalente ao método privado Django:
     *   ControlePontoService._calcular_meta_padrao(data_ref, cidade, uf)
     *
     * @return array{meta_segundos: int, tolerancia_segundos: int, deve_notificar: bool, motivo_ausencia: string|null}
     */
    public static function calcularMetaPadrao(Carbon $data, ?string $cidade, ?string $uf): array
    {
        $isFimDeSemana = $data->isWeekend();   // dayOfWeek >= 5 no Django
        $isFeriado     = FeriadoService::ehFeriado($data->toDateString(), $cidade, $uf);

        $isDiaFolga = $isFimDeSemana || $isFeriado;

        if (!$isDiaFolga) {
            return [
                'meta_segundos'      => self::META_PADRAO,
                'tolerancia_segundos'=> self::TOLERANCIA_PADRAO,
                'deve_notificar'     => true,
                'motivo_ausencia'    => null,
            ];
        }

        $motivo = $isFeriado ? 'Feriado' : 'Fim de Semana';

        return [
            'meta_segundos'      => 0,
            'tolerancia_segundos'=> 0,
            'deve_notificar'     => false,
            'motivo_ausencia'    => $motivo,
        ];
    }
}
