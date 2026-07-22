<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SolidesService
{
    /**
     * Busca o espelho de ponto de um colaborador num intervalo de datas.
     *
     * @param int $colaboradorId
     * @param string $dataInicio Formato Y-m-d
     * @param string $dataFim Formato Y-m-d
     * @return array
     * @throws \Exception
     */
    public static function buscarEspelhoPonto($colaboradorId, $dataInicio, $dataFim)
    {
        $token = env('SOLIDES_API_KEY');
        if (!$token) {
            throw new \Exception("Chave da API da Sólides não configurada no .env (SOLIDES_API_KEY).");
        }

        try {
            // Endpoint fictício base - ajustar com a URL oficial da documentação Sólides
            // $url = "https://api.solides.com.br/v1/pontos/espelho";
            
            // Simulação (Scaffold): 
            // Como o endpoint exato depende da documentação, estamos retornando um mock de estrutura
            // Retire essa simulação e descomente a requisição real quando as chaves/urls estiverem confirmadas.
            
            /*
            $response = Http::withToken($token)
                ->timeout(15)
                ->get($url, [
                    'colaborador_id' => $colaboradorId,
                    'data_inicio'    => $dataInicio,
                    'data_fim'       => $dataFim,
                ]);

            if (!$response->successful()) {
                throw new \Exception("Erro na API Sólides: " . $response->status() . " - " . $response->body());
            }

            $dados = $response->json();
            return $dados['pontos'] ?? [];
            */

            // Retorno Simulado (Mock) Refatorado
            return [
                'colaborador_nome' => 'GABRIEL FERREIRA DE PAULA',
                'colaborador_cargo' => 'ANALISTA ADMINISTRATIVO 2',
                'registros' => [
                    [
                        'data' => \Carbon\Carbon::parse($dataInicio)->format('Y-m-d'), // Simula o 01/02
                        'previstas' => '08:48:00',
                        'trabalhadas' => '14:26:00',
                        'abonadas' => '00:00:00',
                        't1_inicio' => '07:30:00', 't1_fim' => '12:08:00',
                        't2_inicio' => '13:08:00', 't2_fim' => '17:37:00',
                        't3_inicio' => '20:01:00', 't3_fim' => '23:59:00',
                        't4_inicio' => '00:05:00', 't4_fim' => '01:01:00',
                    ],
                    // Pode adicionar mais um dia vazio para simular a diferença
                    [
                        'data' => \Carbon\Carbon::parse($dataInicio)->addDay()->format('Y-m-d'),
                        'previstas' => '08:48:00',
                        'trabalhadas' => '08:48:00',
                        'abonadas' => '00:00:00',
                        't1_inicio' => '08:00:00', 't1_fim' => '12:00:00',
                        't2_inicio' => '13:00:00', 't2_fim' => '17:48:00',
                        't3_inicio' => '-', 't3_fim' => '-',
                        't4_inicio' => '-', 't4_fim' => '-',
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error("SolidesService: Falha ao buscar espelho de ponto - " . $e->getMessage());
            throw $e;
        }
    }
}
