<?php

namespace App\Observers;

use App\Models\Projeto;
use Illuminate\Support\Facades\Cache;

/**
 * ProjetoObserver
 *
 * Equivalente ao signal Django:
 *   @receiver([post_save, post_delete], sender=Projeto)
 *   def limpar_cache_projetos(sender, instance, **kwargs):
 *       cache.delete(f'projeto_info_{instance.pk}')
 *
 * Responsável por invalidar o cache do nome/info de um projeto específico
 * sempre que ele é criado, editado ou excluído.
 * A chave 'projeto_info_{id}' é usada pela rota GET /api/projeto/{id}.
 */
class ProjetoObserver
{
    /**
     * Disparado após: Projeto::create() ou save() de novo registro.
     */
    public function created(Projeto $projeto): void
    {
        $this->limparCache($projeto);
    }

    /**
     * Disparado após: projeto->save() de registro existente.
     * Importante: mudança de nome invalida o cache da API.
     */
    public function updated(Projeto $projeto): void
    {
        $this->limparCache($projeto);
    }

    /**
     * Disparado após: projeto->delete().
     */
    public function deleted(Projeto $projeto): void
    {
        $this->limparCache($projeto);
    }

    private function limparCache(Projeto $projeto): void
    {
        // Equivalente exato ao Django: cache.delete(f'projeto_info_{instance.pk}')
        Cache::forget("projeto_info_{$projeto->id}");
    }
}
