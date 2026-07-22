<?php

namespace App\Services;

use App\Models\Colaborador;
use App\Models\Feriado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FeriadoService
 *
 * Responsável por verificar se uma data é feriado e sincronizar feriados via API.
 *
 * REGRA LEGADA FUNDAMENTAL:
 * A tabela produtividade_feriado NÃO aceita NULL em cidade/uf.
 * Todo feriado (nacional, estadual ou municipal) é CLONADO e salvo
 * individualmente para cada cidade onde existam colaboradores ativos.
 * Ex: "Ano Novo" gera N registros — um para cada cidade monitorada.
 *
 * Estratégia de cache:
 * - Chave: "feriado_{data}_{cidade}_{uf}" (ex: feriado_2025-01-01_SAO_PAULO_SP)
 * - TTL: 86400 segundos (24 horas)
 * - Invalidação: FeriadoObserver ao salvar/excluir
 */
class FeriadoService
{
    /**
     * Verifica se uma data é feriado para uma cidade/UF específica.
     *
     * Como todo feriado (inclusive nacional) é salvo POR CIDADE,
     * basta verificar a combinação exata (data, cidade, uf).
     *
     * @param Carbon|string $data    Data a verificar
     * @param string|null   $cidade  Cidade do colaborador
     * @param string|null   $uf      Estado do colaborador (ex: SP, RJ)
     */
    public static function ehFeriado(Carbon|string $data, ?string $cidade = null, ?string $uf = null): bool
    {
        $dataRef     = $data instanceof Carbon ? $data->toDateString() : $data;
        $cidadeBusca = strtoupper(trim($cidade ?? ''));
        $ufBusca     = strtoupper(trim($uf ?? ''));

        // Chave de cache: "feriado_{data}_{cidade}_{uf}"
        $cidadeCache = str_replace(' ', '_', $cidadeBusca);
        $ufCache     = str_replace(' ', '_', $ufBusca);
        $cacheKey    = "feriado_{$dataRef}_{$cidadeCache}_{$ufCache}";

        return Cache::remember($cacheKey, 86400, function () use ($dataRef, $cidadeBusca, $ufBusca) {
            // Como todo feriado é salvo POR CIDADE (inclusive nacionais),
            // basta buscar a combinação exata (data + cidade + uf).
            return Feriado::where('data', $dataRef)
                ->whereRaw('UPPER(TRIM(cidade)) = ?', [$cidadeBusca])
                ->whereRaw('UPPER(TRIM(uf)) = ?', [$ufBusca])
                ->exists();
        });
    }

    /**
     * Gera a chave de cache para um feriado específico.
     * Usada pelo FeriadoObserver para invalidação.
     */
    public static function gerarCacheKey(string $data, string $cidade, string $uf): string
    {
        $cidadeCache = str_replace(' ', '_', strtoupper(trim($cidade)));
        $ufCache     = str_replace(' ', '_', strtoupper(trim($uf)));
        return "feriado_{$data}_{$cidadeCache}_{$ufCache}";
    }

    // -------------------------------------------------------------------------
    // Buscar cidades-alvo (reutilizado por todos os métodos de sincronização)
    // -------------------------------------------------------------------------

    /**
     * Retorna todas as combinações únicas de (cidade, uf)
     * onde existem colaboradores ativos.
     */
    public static function getCidadesAlvo(): \Illuminate\Support\Collection
    {
        return Colaborador::select('cidade', 'uf')
            ->whereNotNull('cidade')
            ->where('cidade', '!=', '')
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->distinct()
            ->get();
    }

    // -------------------------------------------------------------------------
    // Sincronização de Feriados Nacionais (Loop Duplo)
    // -------------------------------------------------------------------------

    /**
     * Sincroniza os feriados nacionais de um determinado ano.
     *
     * LÓGICA DO LOOP DUPLO:
     * 1. Busca as cidades-alvo (onde há colaboradores)
     * 2. Faz fetch dos feriados nacionais na FeriadosAPI
     * 3. Para CADA feriado da API × CADA cidade-alvo → updateOrCreate
     *
     * Resultado: "Ano Novo" gera N registros, um para cada cidade.
     *
     * @param int $ano Ano a sincronizar
     * @throws \Exception Se o token for inválido ou a API falhar
     */
    public static function sincronizarApi(int $ano): void
    {
        $token = env('FERIADOS_API_KEY');

        if (!$token) {
            throw new \Exception("A chave FERIADOS_API_KEY não foi encontrada no .env.");
        }

        // Passo 1: Buscar cidades-alvo
        $cidadesAlvo = self::getCidadesAlvo();

        if ($cidadesAlvo->isEmpty()) {
            throw new \Exception("Nenhum colaborador com cidade/UF cadastrados. Impossível distribuir feriados.");
        }

        // Passo 2: Fetch na API
        $response = Http::withToken($token)
            ->get("https://feriadosapi.com/api/v1/feriados/nacionais?ano={$ano}");

        if ($response->status() === 401) {
            throw new \Exception("Erro 401: Token da FeriadosAPI inválido ou não autorizado.");
        }

        if (!$response->successful()) {
            throw new \Exception("Erro na API: " . $response->body());
        }

        $feriadosApi = $response->json('feriados');

        if (!isset($feriadosApi) || !is_array($feriadosApi)) {
            throw new \Exception("Estrutura do JSON inesperada. Resposta: " . substr($response->body(), 0, 200));
        }

        // Passo 3: Loop Duplo — distribui cada feriado para cada cidade
        foreach ($feriadosApi as $feriadoApi) {
            $dataOriginal = $feriadoApi['data'] ?? $feriadoApi['date'] ?? null;
            $nome = $feriadoApi['nome'] ?? $feriadoApi['name'] ?? $feriadoApi['descricao'] ?? null;

            if (!$dataOriginal || !$nome) {
                continue;
            }

            try {
                $data = Carbon::createFromFormat('d/m/Y', $dataOriginal)->format('Y-m-d');
            } catch (\Exception $e) {
                $data = Carbon::parse($dataOriginal)->format('Y-m-d');
            }

            foreach ($cidadesAlvo as $local) {
                $cidade = mb_strtoupper(\Illuminate\Support\Str::ascii(trim($local->cidade)));
                $uf = strtoupper(trim($local->uf));

                $jaExiste = Feriado::whereDate('data', $data)
                    ->where('cidade', $cidade)
                    ->where('uf', $uf)
                    ->exists();

                if (!$jaExiste) {
                    Feriado::create([
                        'data'                 => $data,
                        'cidade'               => $cidade,
                        'uf'                   => $uf,
                        'descricao'            => $nome,
                        'inserido_manualmente' => false,
                        'tipo'                 => 'nacional',
                    ]);
                }
            }
        }

        // --- INÍCIO DA SINCRONIZAÇÃO ESTADUAL ---
        $ufsUnicas = $cidadesAlvo->pluck('uf')->map(fn($u) => strtoupper(trim($u)))->unique();

        foreach ($ufsUnicas as $uf) {
            $sigla = strtoupper($uf); // Forçando Maiúsculas
            
            // Faz a requisição para a API estadual
            $responseEstadual = \Illuminate\Support\Facades\Http::withToken($token)
                ->get("https://feriadosapi.com/api/v1/feriados/estado/{$sigla}?ano={$ano}");
                
            // 1. Força o erro se a requisição HTTP falhar (404, 401, 403, etc)
            if (!$responseEstadual->successful()) {
                throw new \Exception("Erro na API Estadual para a UF {$sigla}. Status: " . $responseEstadual->status() . " | Resposta: " . $responseEstadual->body());
            }

            $feriadosEstaduais = $responseEstadual->json('feriados');

            // 2. Força o erro se a chave 'feriados' não existir no JSON
            if ($feriadosEstaduais === null) {
                throw new \Exception("A chave 'feriados' não foi encontrada no JSON Estadual de {$sigla}. Resposta real: " . $responseEstadual->body());
            }
            
            // 3. Força o erro se vier vazio (pode indicar que o plano da API não cobre)
            if (empty($feriadosEstaduais)) {
                throw new \Exception("A API retornou sucesso para {$sigla}, mas a lista de feriados veio vazia. Resposta: " . $responseEstadual->body());
            }

            foreach ($feriadosEstaduais as $feriadoJson) {
                $dataOriginal = $feriadoJson['data'] ?? $feriadoJson['date'] ?? null;
                $nomeEstadual = $feriadoJson['nome'] ?? $feriadoJson['name'] ?? $feriadoJson['descricao'] ?? null;

                if (!$dataOriginal || !$nomeEstadual) {
                    continue;
                }

                try {
                    $dataFormatada = \Carbon\Carbon::createFromFormat('d/m/Y', $dataOriginal)->format('Y-m-d');
                } catch (\Exception $e) {
                    $dataFormatada = \Carbon\Carbon::parse($dataOriginal)->format('Y-m-d');
                }

                // Loop nas cidades alvo filtrando apenas as deste UF
                foreach ($cidadesAlvo as $local) {
                    $ufLocal = strtoupper(trim($local->uf));
                    
                    if ($ufLocal === $uf) {
                        $cidadeLocal = mb_strtoupper(\Illuminate\Support\Str::ascii(trim($local->cidade)));

                        $jaExiste = Feriado::whereDate('data', $dataFormatada)
                            ->where('cidade', $cidadeLocal)
                            ->where('uf', $ufLocal)
                            ->exists();

                        if (!$jaExiste) {
                            Feriado::create([
                                'data'                 => $dataFormatada,
                                'cidade'               => $cidadeLocal,
                                'uf'                   => $ufLocal,
                                'descricao'            => $nomeEstadual,
                                'tipo'                 => 'estadual',
                                'inserido_manualmente' => false
                            ]);
                        }
                    }
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Sincronização de Feriados Estaduais (Loop Duplo com filtro de UF)
    // -------------------------------------------------------------------------

    /**
     * Sincroniza feriados estaduais de uma UF específica.
     *
     * Aplica a mesma lógica de loop duplo, mas com filtro:
     * só insere nas cidades daquele estado ($local->uf === $uf).
     *
     * @param int    $ano Ano a consultar
     * @param string $uf  Sigla do estado (ex: SP)
     * @return bool  true se sincronizou, false se sem dados
     */
    public static function sincronizarFeriadosEstaduais(int $ano, string $uf): bool
    {
        $token = env('FERIADOS_API_KEY');

        if (!$token) {
            return false;
        }

        try {
            $ufNorm = strtoupper(trim($uf));

            $response = Http::withToken($token)
                ->timeout(15)
                ->get("https://feriadosapi.com/api/v1/feriados/estado", [
                    'ano' => $ano,
                    'uf'  => $ufNorm,
                ]);

            if (!$response->successful()) {
                return false;
            }

            $feriadosApi = $response->json('feriados');

            if (!is_array($feriadosApi) || empty($feriadosApi)) {
                return false;
            }

            // Filtrar cidades-alvo que pertencem a esta UF
            $cidadesDoEstado = self::getCidadesAlvo()->filter(function ($local) use ($ufNorm) {
                return strtoupper(trim($local->uf)) === $ufNorm;
            });

            if ($cidadesDoEstado->isEmpty()) {
                return false;
            }

            // Loop duplo com filtro de UF
            foreach ($feriadosApi as $feriadoApi) {
                $dataOriginal = $feriadoApi['data'] ?? $feriadoApi['date'] ?? null;
                $nome = $feriadoApi['nome'] ?? $feriadoApi['name'] ?? $feriadoApi['descricao'] ?? null;

                if (!$dataOriginal || !$nome) {
                    continue;
                }

                try {
                    $data = Carbon::createFromFormat('d/m/Y', $dataOriginal)->format('Y-m-d');
                } catch (\Exception $e) {
                    $data = Carbon::parse($dataOriginal)->format('Y-m-d');
                }

                foreach ($cidadesDoEstado as $local) {
                    $cidade = mb_strtoupper(\Illuminate\Support\Str::ascii(trim($local->cidade)));

                    $jaExiste = Feriado::whereDate('data', $data)
                        ->where('cidade', $cidade)
                        ->where('uf', $ufNorm)
                        ->exists();

                    if (!$jaExiste) {
                        Feriado::create([
                            'data'                 => $data,
                            'cidade'               => $cidade,
                            'uf'                   => $ufNorm,
                            'descricao'            => $nome,
                            'inserido_manualmente' => false,
                            'tipo'                 => 'estadual',
                        ]);
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::warning("FeriadoService: Falha ao sincronizar estaduais para UF {$uf}: " . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Sincronização de Feriados Municipais (por cidade específica)
    // -------------------------------------------------------------------------

    /**
     * Sincroniza feriados municipais de uma cidade/UF específica.
     *
     * Como feriados municipais já são por cidade, não precisa de loop duplo —
     * o registro já é salvo com a cidade/uf corretos.
     *
     * Se a API não retornar dados (cidade pequena/sem suporte), retorna false
     * para sinalizar pendência na tela — nunca quebra o sistema.
     *
     * @param int    $ano    Ano a consultar
     * @param string $cidade Nome da cidade
     * @param string $uf     Sigla do estado (ex: SP)
     * @return bool  true se sincronizou com sucesso, false se a API não tem dados
     */
    public static function sincronizarFeriadosMunicipais(int $ano, string $cidade, string $uf): bool
    {
        $token = env('FERIADOS_API_KEY');

        if (!$token) {
            return false;
        }

        try {
            $ufNorm = strtoupper(trim($uf));

            $response = Http::withToken($token)
                ->timeout(15)
                ->get("https://feriadosapi.com/api/v1/feriados/municipais", [
                    'ano'    => $ano,
                    'cidade' => trim($cidade),
                    'uf'     => $ufNorm,
                ]);

            if (!$response->successful()) {
                return false;
            }

            $feriadosApi = $response->json('feriados');

            if (!is_array($feriadosApi) || empty($feriadosApi)) {
                return false;
            }

            foreach ($feriadosApi as $feriadoApi) {
                $dataOriginal = $feriadoApi['data'] ?? $feriadoApi['date'] ?? null;
                $nome = $feriadoApi['nome'] ?? $feriadoApi['name'] ?? $feriadoApi['descricao'] ?? null;

                if ($dataOriginal && $nome) {
                    try {
                        $data = Carbon::createFromFormat('d/m/Y', $dataOriginal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $data = Carbon::parse($dataOriginal)->format('Y-m-d');
                    }
                    $cidadeNorm = mb_strtoupper(\Illuminate\Support\Str::ascii(trim($cidade)));
                    
                    $jaExiste = Feriado::whereDate('data', $data)
                        ->where('cidade', $cidadeNorm)
                        ->where('uf', $ufNorm)
                        ->exists();

                    if (!$jaExiste) {
                        Feriado::create([
                            'data'                 => $data,
                            'cidade'               => $cidadeNorm,
                            'uf'                   => $ufNorm,
                            'descricao'            => $nome,
                            'inserido_manualmente' => false,
                            'tipo'                 => 'municipal',
                        ]);
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::warning("FeriadoService: Falha ao sincronizar municipais para {$cidade}/{$uf}: " . $e->getMessage());
            return false;
        }
    }
}
