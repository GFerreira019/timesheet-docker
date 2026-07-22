<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * RateioService
 *
 * Equivalente à função distribuir_horarios_com_gap() do utils.py do Django.
 * Algoritmo de rateio proporcional de horas entre múltiplas obras.
 *
 * REGRA DE NEGÓCIO:
 * Dado um período (hora_inicio → hora_termino) e N obras/clientes,
 * divide o período em N fatias de tempo IGUAIS e CONSECUTIVAS (sem gap).
 * Cada fatia pode sofrer arredondamento de até 1 segundo para garantir
 * que a soma total seja exatamente o período original.
 *
 * Exemplo:
 *   Período: 08:00 às 17:00 (9 horas) em 3 obras
 *   Saída:
 *     Obra 1: 08:00 → 11:00 (3h)
 *     Obra 2: 11:00 → 14:00 (3h)
 *     Obra 3: 14:00 → 17:00 (3h)
 */
class RateioService
{
    /**
     * Distribui o período entre N obras sem sobreposição e sem gap.
     *
     * Equivalente exato ao Django:
     *   distribuir_horarios_com_gap(hora_inicio, hora_termino, qtd_obras)
     *
     * @param Carbon $inicio    Hora de início (com data)
     * @param Carbon $termino   Hora de término (com data — pode ser dia seguinte se overnight)
     * @param int    $qtdObras  Número de obras a ratear (mínimo 2)
     * @return array            Array de ['inicio' => Carbon, 'termino' => Carbon]
     *
     * @throws \InvalidArgumentException Se qtdObras < 2 ou termino <= inicio
     */
    public static function distribuirHorariosComGap(Carbon $inicio, Carbon $termino, int $qtdObras): array
    {
        if ($qtdObras < 2) {
            throw new \InvalidArgumentException('O rateio requer no mínimo 2 obras.');
        }

        // Trata overnight — mesmo comportamento do Django
        $terminoEfetivo = $termino->clone();
        if ($terminoEfetivo->lte($inicio)) {
            $terminoEfetivo->addDay();
        }

        $totalSegundos = $inicio->diffInSeconds($terminoEfetivo);

        if ($totalSegundos <= 0) {
            throw new \InvalidArgumentException('O horário de término deve ser posterior ao de início.');
        }

        // Duração base de cada fatia (divisão inteira)
        $duracaoBase     = intdiv($totalSegundos, $qtdObras);
        // Segundos extras (arredondamento) distribuídos nas primeiras fatias
        $segundosExtras  = $totalSegundos % $qtdObras;

        $fatias   = [];
        $pontoRef = $inicio->clone();

        for ($i = 0; $i < $qtdObras; $i++) {
            $duracaoFatia = $duracaoBase + ($i < $segundosExtras ? 1 : 0);

            $inicioFatia  = $pontoRef->clone();
            $terminoFatia = $pontoRef->clone()->addSeconds($duracaoFatia);

            $fatias[] = [
                'inicio'  => $inicioFatia,
                'termino' => $terminoFatia,
            ];

            $pontoRef = $terminoFatia;
        }

        return $fatias;
    }

    /**
     * Verifica se um conjunto de fatias cobre o período original sem gap.
     * Útil para testes e validações.
     *
     * @param array  $fatias   Resultado de distribuirHorariosComGap()
     * @param Carbon $inicio   Início esperado
     * @param Carbon $termino  Término esperado
     */
    public static function verificarIntegridade(array $fatias, Carbon $inicio, Carbon $termino): bool
    {
        if (empty($fatias)) {
            return false;
        }

        $terminoEfetivo = $termino->clone();
        if ($terminoEfetivo->lte($inicio)) {
            $terminoEfetivo->addDay();
        }

        // Primeira fatia deve começar no início
        if (!$fatias[0]['inicio']->equalTo($inicio)) {
            return false;
        }

        // Última fatia deve terminar no término
        $ultimaFatia = end($fatias);
        if (!$ultimaFatia['termino']->equalTo($terminoEfetivo)) {
            return false;
        }

        // Cada fatia deve começar exatamente onde a anterior terminou
        for ($i = 1; $i < count($fatias); $i++) {
            if (!$fatias[$i]['inicio']->equalTo($fatias[$i - 1]['termino'])) {
                return false;
            }
        }

        return true;
    }
}
