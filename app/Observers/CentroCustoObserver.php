<?php

namespace App\Observers;

use App\Models\CentroCusto;
use Illuminate\Support\Facades\Cache;

/**
 * CentroCustoObserver
 *
 * Equivalente ao signal Django:
 *   @receiver([post_save, post_delete], sender=CentroCusto)
 *   def limpar_cache_centro_custo(sender, instance, **kwargs):
 *       cache.delete(f'cc_info_{instance.pk}')
 *
 * Responsável por invalidar o cache de regras de um centro de custo específico
 * (em especial o campo permite_alocacao) sempre que é alterado.
 * A chave 'cc_info_{id}' é usada pela rota GET /api/centro-custo/{id}.
 */
class CentroCustoObserver
{
    /**
     * Disparado após: CentroCusto::create() ou save() de novo registro.
     */
    public function created(CentroCusto $centroCusto): void
    {
        $this->limparCache($centroCusto);
    }

    /**
     * Disparado após: centroCusto->save() de registro existente.
     * Crítico: mudança em permite_alocacao afeta o formulário de apontamento.
     */
    public function updated(CentroCusto $centroCusto): void
    {
        $this->limparCache($centroCusto);
    }

    /**
     * Disparado após: centroCusto->delete().
     */
    public function deleted(CentroCusto $centroCusto): void
    {
        $this->limparCache($centroCusto);
    }

    private function limparCache(CentroCusto $centroCusto): void
    {
        // Equivalente exato ao Django: cache.delete(f'cc_info_{instance.pk}')
        Cache::forget("cc_info_{$centroCusto->id}");
    }
}
