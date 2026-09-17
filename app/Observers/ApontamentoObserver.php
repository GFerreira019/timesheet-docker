<?php

namespace App\Observers;

use App\Models\Apontamento;
use App\Models\ColaboradorHistorico;

class ApontamentoObserver
{
    /**
     * Handle the Apontamento "saving" event.
     * Captura o cargo do colaborador no exato momento da data do apontamento
     * (Viagem no tempo via ColaboradorHistorico).
     */
    public function saving(Apontamento $apontamento)
    {
        if (!$apontamento->colaborador_id || !$apontamento->data_apontamento) {
            return;
        }

        // Previne Lazy Loading explicitamente carregando a relação caso ainda não esteja na memória
        $apontamento->loadMissing('colaborador');
        $colaborador = $apontamento->colaborador;
        
        // Se o colaborador não for encontrado (ex: deletado ou erro de dados), sai silenciosamente
        if (!$colaborador) {
            return;
        }

        $cargoData = $colaborador->cargo; // Fallback: Cargo Atual

        // Busca a mudança de cargo imediatamente subsequente à data do apontamento
        $historicoSubsequente = ColaboradorHistorico::where('colaborador_id', $colaborador->id)
            ->where('data_vigencia', '>', $apontamento->data_apontamento)
            ->whereJsonContains('campos_alterados', 'cargo')
            ->orderBy('data_vigencia', 'asc')
            ->first();

        // Se encontrou uma mudança de cargo DEPOIS da data do apontamento, 
        // significa que o cargo ANTERIOR a essa mudança era o vigente na data do apontamento.
        if ($historicoSubsequente && isset($historicoSubsequente->dados_anteriores['cargo'])) {
            $cargoData = $historicoSubsequente->dados_anteriores['cargo'];
        }

        $apontamento->cargo_snapshot = $cargoData;
    }
}
