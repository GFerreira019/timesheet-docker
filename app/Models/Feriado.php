<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Eloquent equivalente ao model Django: Feriado
 *
 * Feriados por data, cidade e UF — usado pelo FeriadoService para
 * determinar se um dia é feriado municipal, estadual ou nacional.
 * Restrição unique em (data, cidade, uf) é garantida pela migration.
 *
 * @property int    $id
 * @property string $data        formato Y-m-d
 * @property string $descricao
 * @property string $cidade
 * @property string $uf
 */
class Feriado extends Model
{
    protected $table = 'produtividade_feriado';

    protected $fillable = [
        'data',
        'descricao',
        'cidade',
        'uf',
        'inserido_manualmente',
        'tipo',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django: "{descricao} ({cidade}/{uf})"
     */
    public function __toString(): string
    {
        return "{$this->descricao} ({$this->cidade}/{$this->uf})";
    }
}
