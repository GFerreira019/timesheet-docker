<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent equivalente ao model Django: CentroCusto
 *
 * Entidade para alocação de custos e justificativas operacionais.
 * Quando permite_alocacao = true, o formulário solicita Código de Obra ou Cliente.
 *
 * @property int    $id
 * @property string $nome
 * @property bool   $permite_alocacao
 * @property bool   $ativo
 */
class CentroCusto extends Model
{
    protected $table = 'produtividade_centrocusto';

    protected $fillable = [
        'nome',
        'permite_alocacao',
        'ativo',
    ];

    protected $casts = [
        'permite_alocacao' => 'boolean',
        'ativo'            => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Apontamentos que usam este centro de custo.
     */
    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'centro_custo_id');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        return $this->nome;
    }
}
