<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent equivalente ao model Django: CodigoCliente
 *
 * Cadastro de Códigos Gerais de Cliente padronizados com 4 dígitos.
 * Validação do regex (^\d{4}$) é feita no FormRequest (Etapa 5).
 *
 * @property int    $id
 * @property string $codigo  4 dígitos numéricos
 * @property string $nome
 * @property bool   $ativo
 */
class CodigoCliente extends Model
{
    protected $table = 'produtividade_codigocliente';

    protected $fillable = [
        'codigo',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'codigo_cliente_id');
    }

    public function projetos(): HasMany
    {
        return $this->hasMany(Projeto::class, 'codigo_cliente_id');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django: "{codigo} - {nome}"
     */
    public function __toString(): string
    {
        return "{$this->codigo} - {$this->nome}";
    }
}
