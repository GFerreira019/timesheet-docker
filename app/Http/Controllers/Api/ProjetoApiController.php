<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projeto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * ProjetoApiController
 *
 * Equivalente à API Django:
 *   get_projeto_info_ajax(request, projeto_id) → info()
 *
 * GET /api/projeto/{id}
 *   Retorna o nome do projeto, cacheado por 12h.
 *   Chave: 'projeto_info_{id}' — invalidada pelo ProjetoObserver.
 */
class ProjetoApiController extends Controller
{
    /**
     * Retorna o nome do projeto pelo ID.
     * Equivalente ao get_projeto_info_ajax() do Django (apis.py L25-34).
     *
     * Cache de 12h (43200s) — equivalente ao cache.set(cache_key, nome_projeto, 43200).
     *
     * GET /api/projeto/{id}
     */
    public function info(int $id): JsonResponse
    {
        $cacheKey = "projeto_info_{$id}";

        $nomeProjeto = Cache::remember($cacheKey, 43200, function () use ($id) {
            $projeto = Projeto::findOrFail($id);
            return $projeto->nome;
        });

        return response()->json(['nome_projeto' => $nomeProjeto]);
    }
}
