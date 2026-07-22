<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CentroCusto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * CentroCustoApiController
 *
 * Equivalente à API Django:
 *   get_centro_custo_info_ajax(request, cc_id) → info()
 *
 * GET /api/centro-custo/{id}
 *   Retorna o campo `permite_alocacao` do centro de custo.
 *   Resultado cacheado por 12h.
 *   Chave: 'cc_info_{id}' — invalidada pelo CentroCustoObserver.
 *
 * Usado pelo front-end JavaScript do formulário de apontamento para:
 *   - Exibir/ocultar o campo de Obra/Cliente quando local=EXT
 *   - Determinar se a seleção de projeto é obrigatória para este CC
 */
class CentroCustoApiController extends Controller
{
    /**
     * Retorna permite_alocacao do centro de custo.
     * Equivalente ao get_centro_custo_info_ajax() do Django (apis.py L56-65).
     *
     * Nota Django: usa `if permite is None:` para distinguir cache miss de False.
     * Laravel: Cache::remember() trata corretamente (null = cache miss).
     *
     * GET /api/centro-custo/{id}
     */
    public function info(int $id): JsonResponse
    {
        $cacheKey = "cc_info_{$id}";

        // Cache de 12h — equivalente ao cache.set(cache_key, permite, 43200)
        // Nota: precisamos guardar o bool, não null (para distinguir de cache miss)
        $permite = Cache::remember($cacheKey, 43200, function () use ($id) {
            $cc = CentroCusto::findOrFail($id);
            // Retorna explicitamente true/false (nunca null) para que
            // o cache funcione corretamente com o centinela is None do Django
            return (bool) $cc->permite_alocacao;
        });

        return response()->json(['permite_alocacao' => $permite]);
    }
}
