<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * ColaboradorApiController
 *
 * Equivalente às APIs Django:
 *   get_colaborador_info_ajax(request, colaborador_id)  → info()
 *   get_auxiliares_ajax(request)                        → auxiliares()
 *
 * GET /api/colaborador/{id}      — retorna cargo do colaborador
 * GET /api/auxiliares            — lista auxiliares (cached 12h)
 */
class ColaboradorApiController extends Controller
{
    /**
     * Retorna o cargo do colaborador pelo ID.
     * Equivalente ao get_colaborador_info_ajax() do Django (apis.py L37-39).
     *
     * GET /api/colaborador/{id}
     */
    public function info(int $id): JsonResponse
    {
        $colaborador = Colaborador::findOrFail($id);

        return response()->json([
            'cargo' => $colaborador->cargo,
        ]);
    }

    /**
     * Lista todos os auxiliares técnicos e oficiais de sistemas.
     * Resultado cacheado por 12h (equivalente ao cache.set(cache_key, auxs, 43200) do Django).
     *
     * Equivalente ao get_auxiliares_ajax() do Django (apis.py L42-53).
     * Chave de cache: 'api_lista_auxiliares' — invalidada pelo ColaboradorObserver.
     *
     * GET /api/auxiliares
     */
    public function auxiliares(): JsonResponse
    {
        $cacheKey = 'api_lista_auxiliares';

        // Cache de 12h (43200 segundos) — equivalente ao Django
        $auxiliares = Cache::remember($cacheKey, 43200, function () {
            return Colaborador::ativos()
                ->whereIn('cargo', ['AUXILIAR TECNICO', 'OFICIAL DE SISTEMAS'])
                ->orderBy('nome_completo')
                ->get(['id', 'nome_completo', 'data_demissao'])
                ->map(fn($a) => ['id' => $a->id, 'nome_completo' => $a->nome_exibicao])
                ->values()
                ->all();
        });

        return response()->json(['auxiliares' => $auxiliares]);
    }
}
