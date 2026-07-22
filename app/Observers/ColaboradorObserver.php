<?php

namespace App\Observers;

use App\Models\Colaborador;
use App\Models\ColaboradorHistorico;
use Illuminate\Support\Facades\Cache;

/**
 * ColaboradorObserver
 *
 * Equivalente ao signal Django:
 *   @receiver([post_save, post_delete], sender=Colaborador)
 *   def limpar_cache_colaboradores(sender, instance, **kwargs):
 *       cache.delete('api_lista_auxiliares')
 *
 * Responsável por invalidar o cache da lista de auxiliares sempre que
 * um colaborador é criado, editado ou excluído.
 * A chave 'api_lista_auxiliares' é usada pela rota GET /api/auxiliares.
 */
class ColaboradorObserver
{
    /** Cache key equivalente ao Django: 'api_lista_auxiliares' */
    private const CACHE_KEY = 'api_lista_auxiliares';

    /**
     * Disparado após: Colaborador::create() ou save() de novo registro.
     * Equivalente ao post_save (created=True) do Django.
     */
    public function created(Colaborador $colaborador): void
    {
        $this->limparCache();
    }

    /**
     * Disparado após: colaborador->save() de registro existente.
     * Equivalente ao post_save (created=False) do Django.
     * Importante: mudanças de cargo afetam a lista de auxiliares.
     */
    public function updated(Colaborador $colaborador): void
    {
        $this->limparCache();

        $changes = $colaborador->getChanges();
        
        // Remove 'updated_at' since we don't care about it for audit purposes
        unset($changes['updated_at']);

        if (!empty($changes)) {
            $original = $colaborador->getOriginal();
            $dadosAnteriores = array_intersect_key($original, $changes);

            ColaboradorHistorico::create([
                'colaborador_id' => $colaborador->id,
                'user_id_alteracao' => auth()->id(),
                'dados_anteriores' => $dadosAnteriores,
                'campos_alterados' => $changes,
                'data_vigencia' => $colaborador->dataVigenciaVirtual, // Passa a vigência para o histórico
            ]);
        }
    }

    /**
     * Disparado após: colaborador->delete().
     * Equivalente ao post_delete do Django.
     */
    public function deleted(Colaborador $colaborador): void
    {
        $this->limparCache();
    }

    /**
     * Disparado após: Colaborador::withTrashed()->restore() (se usar SoftDeletes no futuro).
     */
    public function restored(Colaborador $colaborador): void
    {
        $this->limparCache();
    }

    private function limparCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
