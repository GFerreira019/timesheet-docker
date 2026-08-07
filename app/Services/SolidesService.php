<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Colaborador;
use App\Models\SolidesPonto;

class SolidesService
{
    /**
     * Busca os registros de ponto na API da Sólides e sincroniza no banco local.
     */
    public static function buscarEspelhoPonto($colaboradorId = null, $dataInicio = null, $dataFim = null)
    {
        try {
            $baseUrl = config('services.solides.url');
            $url = rtrim($baseUrl, '/') . '/punch/';
            $token = 'Basic ' . config('services.solides.token');

            $page = 0;
            $size = 100;
            $hasMore = true;

            while ($hasMore) {
                // Parâmetros da query string base
                $queryParams = [
                    'page'       => $page,
                    'size'       => $size,
                    'adjustment' => 'true',
                ];

                // Adiciona parâmetros opcionais se informados
                if ($colaboradorId) {
                    $colab = Colaborador::find($colaboradorId);
                    if (!$colab || empty($colab->solides_id)) {
                        // Se chegou até aqui sem ID, aborta a função imediatamente para não travar o servidor
                        throw new \Exception("Tentativa de sincronização bloqueada: Colaborador sem solides_id.");
                    }
                    $queryParams['employeeId'] = $colab->solides_id;
                }
                // Conversão de Datas para Milissegundos, exigido pela API
                if ($dataInicio) {
                    $inicioCarbon = $dataInicio instanceof Carbon ? clone $dataInicio : Carbon::parse($dataInicio);
                    $queryParams['startDateInMillis'] = $inicioCarbon->startOfDay()->valueOf();
                }
                
                if ($dataFim) {
                    $fimCarbon = $dataFim instanceof Carbon ? clone $dataFim : Carbon::parse($dataFim);
                    $queryParams['endDateInMillis'] = $fimCarbon->endOfDay()->valueOf();
                }

                // Log para Debug antes da requisição
                Log::info("Parâmetros enviados Sólides:", $queryParams);

                // Requisição HTTP conforme regras (Headers estritos)
                $response = Http::withHeaders([
                    'Authorization' => $token,
                    'accept'        => 'application/json;charset=UTF-8'
                ])->get($url, $queryParams);

                if (!$response->successful()) {
                    throw new \Exception("Erro na API Sólides: " . $response->status() . " - " . $response->body());
                }

                $dados = $response->json();
                
                $items = isset($dados['content']) ? $dados['content'] : (isset($dados['data']) ? $dados['data'] : $dados);

                if (empty($items)) {
                    $hasMore = false;
                    break;
                }

                // Loop dos Resultados
                foreach ($items as $item) {
                    $solidesPontoId = $item['id'] ?? null;
                    $employeeId = $item['employee']['id'] ?? $item['employeeId'] ?? null;

                    if (!$solidesPontoId || !$employeeId) {
                        continue;
                    }

                    // solides_id foi movido para a tabela users (centralizado no ERP).
                    // A busca é feita via relacionamento: colaborador → user → solides_id.
                    $colaboradorLocal = Colaborador::whereHas('user', function ($q) use ($employeeId) {
                        $q->where('solides_id', $employeeId);
                    })->first();

                    if (!$colaboradorLocal) {
                        // Se não encontrar o colaborador localmente, grave um Log::warning e faça um continue
                        Log::warning("Colaborador da Solides (ID: {$employeeId}) não encontrado no banco local. Batida {$solidesPontoId} ignorada.");
                        continue;
                    }

                    // 2. Conversão de Datas (Crucial)
                    $horaEntrada = null;
                    $horaSaida = null;
                    $dataRegistro = $item['date'] ?? null;

                    if (isset($item['dateInFull']) && $item['dateInFull']) {
                        $dtIn = Carbon::createFromTimestampMs($item['dateInFull'])->setTimezone('America/Sao_Paulo');
                        $horaEntrada = $dtIn->format('H:i:s');
                        if (!$dataRegistro) {
                            $dataRegistro = $dtIn->format('Y-m-d');
                        }
                    }

                    if (isset($item['dateOutFull']) && $item['dateOutFull']) {
                        $dtOut = Carbon::createFromTimestampMs($item['dateOutFull'])->setTimezone('America/Sao_Paulo');
                        $horaSaida = $dtOut->format('H:i:s');
                    }

                    // 2.5 Lidar com Ajustes/Abonos
                    $isAjustado = false;
                    $justificativa = null;
                    $horasAbonadas = null;
                    $diaTrabalhado = true;

                    if (isset($item['adjust']) && $item['adjust'] === true && isset($item['adjustmentReasonRecord'])) {
                        $isAjustado = true;
                        $justificativa = $item['adjustmentReason']['description'] ?? null;
                        
                        if (isset($item['fullDayAdjustment']) && $item['fullDayAdjustment'] === true) {
                            $diaTrabalhado = false;
                        }

                        $startDate = $item['adjustmentReasonRecord']['startDate'] ?? null;
                        $endDate = $item['adjustmentReasonRecord']['endDate'] ?? null;

                        if ($startDate && $endDate) {
                            try {
                                $startCarbon = Carbon::parse($startDate);
                                $endCarbon = Carbon::parse($endDate);
                                // $endCarbon->floatDiffInHours($startCarbon) traz as horas decimais (ex: 8.5)
                                $horasAbonadas = round($startCarbon->floatDiffInHours($endCarbon), 2);
                            } catch (\Exception $e) {
                                Log::warning("Falha ao calcular horas abonadas no SolidesService para registro {$solidesPontoId}: " . $e->getMessage());
                            }
                        }
                    }

                    // 3. Upsert no Banco (Evitar Duplicidade)
                    SolidesPonto::updateOrCreate(
                        ['solides_ponto_id' => $solidesPontoId],
                        [
                            'colaborador_id' => $colaboradorLocal->id,
                            'data'           => $dataRegistro,
                            'hora_entrada'   => $horaEntrada,
                            'hora_saida'     => $horaSaida,
                            'status'         => $item['status'] ?? null,
                            'is_ajustado'    => $isAjustado,
                            'justificativa'  => $justificativa,
                            'horas_abonadas' => $horasAbonadas,
                            'dia_trabalhado' => $diaTrabalhado,
                        ]
                    );
                }

                if (count($items) < $size) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            }

        } catch (\Exception $e) {
            // Envolva a requisição da API em um bloco try...catch capturando as exceções e registrando os erros no Log::error.
            Log::error("Erro no SolidesService@buscarEspelhoPonto: " . $e->getMessage());
            throw $e;
        }
    }
}

