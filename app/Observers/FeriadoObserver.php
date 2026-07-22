<?php

namespace App\Observers;

use App\Models\Feriado;
use App\Services\FeriadoService;
use Illuminate\Support\Facades\Cache;

/**
 * FeriadoObserver
 *
 * Equivalente ao signal Django:
 *   @receiver([post_save, post_delete], sender=Feriado)
 *   def limpar_cache_feriados(sender, instance, **kwargs):
 *       if instance.data and instance.cidade and instance.uf:
 *           data_str = instance.data.strftime('%Y-%m-%d')
 *           cidade_str = instance.cidade.strip().upper()
 *           uf_str = instance.uf.strip().upper()
 *           cache_key = f"feriado_{data_str}_{cidade_str}_{uf_str}"
 *           cache.delete(cache_key)
 *
 * Responsável por invalidar o cache de verificação de feriado para a
 * combinação exata de (data, cidade, uf) que foi alterada.
 * Usa o FeriadoService.gerarCacheKey() para manter a geração da chave
 * em um único lugar (DRY).
 */
class FeriadoObserver
{
    /**
     * Disparado após: Feriado::create()
     * Cache não existe ainda, mas invalida por consistência.
     */
    public function created(Feriado $feriado): void
    {
        $this->limparCache($feriado);
    }

    /**
     * Disparado após: feriado->save() de registro existente.
     * Mudança de data, cidade ou UF requer invalidação imediata.
     */
    public function updated(Feriado $feriado): void
    {
        // Se a data/cidade/uf foram alterados, precisa invalidar a chave ANTIGA também.
        // getOriginal() retorna os valores antes da edição — sem equivalente direto no Django.
        if ($feriado->wasChanged(['data', 'cidade', 'uf'])) {
            $dataOriginal   = $feriado->getOriginal('data');
            $cidadeOriginal = $feriado->getOriginal('cidade');
            $ufOriginal     = $feriado->getOriginal('uf');

            if ($dataOriginal && $cidadeOriginal && $ufOriginal) {
                $chaveAntiga = FeriadoService::gerarCacheKey(
                    is_string($dataOriginal) ? $dataOriginal : $dataOriginal->toDateString(),
                    $cidadeOriginal,
                    $ufOriginal
                );
                Cache::forget($chaveAntiga);
            }
        }

        $this->limparCache($feriado);
    }

    /**
     * Disparado após: feriado->delete().
     * Equivalente ao post_delete do Django — invalida a chave exata.
     */
    public function deleted(Feriado $feriado): void
    {
        $this->limparCache($feriado);
    }

    private function limparCache(Feriado $feriado): void
    {
        if (!$feriado->data || !$feriado->cidade || !$feriado->uf) {
            return;
        }

        $dataStr = $feriado->data instanceof \Carbon\Carbon
            ? $feriado->data->toDateString()
            : (string) $feriado->data;

        // Equivalente exato ao Django: cache.delete(cache_key)
        // Usa FeriadoService.gerarCacheKey() para consistência da chave
        $cacheKey = FeriadoService::gerarCacheKey($dataStr, $feriado->cidade, $feriado->uf);
        Cache::forget($cacheKey);
    }
}
