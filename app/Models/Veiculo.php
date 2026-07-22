<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent equivalente ao model Django: Veiculo
 *
 * Cadastro da frota oficial e veículos de apoio da empresa.
 *
 * @property int         $id
 * @property string      $placa
 * @property string|null $descricao
 */
class Veiculo extends Model
{

    protected $table = 'produtividade_veiculo';

    public function getTable()
    {
        return config('erp.tabelas.veiculo', 'produtividade_veiculo');
    }

    protected $fillable = [
        'placa',
        'descricao',
        'status',
        'sistema_rastreamento',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'veiculo_id');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers / Scopes
    // -------------------------------------------------------------------------

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Equivalente ao __str__ do Django:
     * Se tem descricao: "{descricao} - {placa}"
     * Senão: "{placa}"
     */
    public function __toString(): string
    {
        return $this->descricao
            ? "{$this->descricao} - {$this->placa}"
            : $this->placa;
    }
}
