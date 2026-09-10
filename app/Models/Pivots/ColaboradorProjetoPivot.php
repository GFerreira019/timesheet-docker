<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\ProjetoOperacional;
use App\Models\Colaborador;

class ColaboradorProjetoPivot extends Pivot
{
    protected $table = 'colaborador_projeto_gerenciado';

    /**
     * Opcional: Define a propriedade caso necessite manipular incrementos, timestamps, etc.
     */
    public $incrementing = false;

    protected static function booted()
    {
        /**
         * Evento disparado quando um registro é INSERIDO na tabela pivot
         * (ex: durante attach, syncWithoutDetaching ou sync que resulte em nova linha).
         */
        static::created(function ($pivot) {
            $projeto = ProjetoOperacional::find($pivot->projeto_operacional_id);
            
            if ($projeto && $projeto->cliente_operacional_id) {
                $colaborador = Colaborador::find($pivot->colaborador_id);
                
                if ($colaborador) {
                    // Vincula o acesso ao cliente do projeto (Cascata)
                    $colaborador->clientesGerenciados()->syncWithoutDetaching([$projeto->cliente_operacional_id]);
                }
            }
        });

        /**
         * Evento disparado quando um registro é DELETADO da tabela pivot
         * (ex: durante detach ou sync que remova linhas).
         */
        static::deleted(function ($pivot) {
            $projeto = ProjetoOperacional::find($pivot->projeto_operacional_id);
            
            if ($projeto && $projeto->cliente_operacional_id) {
                $colaborador = Colaborador::find($pivot->colaborador_id);
                
                if ($colaborador) {
                    // Verifica se o colaborador ainda gerencia algum outro projeto deste mesmo cliente
                    $aindaTemAcesso = $colaborador->projetosGerenciados()
                        ->where('cliente_operacional_id', $projeto->cliente_operacional_id)
                        ->exists();

                    // Se não houver mais nenhum projeto deste cliente, revoga o acesso ao cliente
                    if (!$aindaTemAcesso) {
                        $colaborador->clientesGerenciados()->detach($projeto->cliente_operacional_id);
                    }
                }
            }
        });
    }
}
