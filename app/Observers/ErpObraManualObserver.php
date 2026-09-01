<?php

namespace App\Observers;

use App\Models\ErpObraManual;
use App\Models\ControleProjetoHistorico;

class ErpObraManualObserver
{
    /**
     * Handle the ErpObraManual "saved" event.
     * We use 'saved' to catch both creates and updates if needed, 
     * but the prompt specifically mentions capturing old/new data on update.
     * I'll use 'updated' to specifically audit changes.
     *
     * @param  \App\Models\ErpObraManual  $obra
     * @return void
     */
    public function updated(ErpObraManual $obra)
    {
        try {
            $snapshot = $obra->toArray();

            $ultimaEdicao = ControleProjetoHistorico::where('projeto_original_id', $obra->id)
                ->orderBy('numero_edicao', 'desc')
                ->first();

            $proximoNumero = $ultimaEdicao ? ($ultimaEdicao->numero_edicao + 1) : 1;

            ControleProjetoHistorico::create([
                'projeto_original_id' => $obra->id,
                'dados_snapshot' => $snapshot,
                'editado_por_id' => auth()->id(), // Pega o usuário logado
                'numero_edicao' => $proximoNumero,
                'data_edicao' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro no ErpObraManualObserver (updated): " . $e->getMessage());
        }
    }
    
    /**
     * Handle the ErpObraManual "created" event.
     * Optionally log the initial creation as edition 1.
     *
     * @param  \App\Models\ErpObraManual  $obra
     * @return void
     */
    public function created(ErpObraManual $obra)
    {
        try {
            ControleProjetoHistorico::create([
                'projeto_original_id' => $obra->id,
                'dados_snapshot' => $obra->toArray(),
                'editado_por_id' => auth()->id(),
                'numero_edicao' => 1,
                'data_edicao' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro no ErpObraManualObserver (created): " . $e->getMessage());
        }
    }
}
