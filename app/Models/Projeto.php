<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent equivalente ao model Django: Projeto
 *
 * Cadastro centralizado de Obras e Projetos ativos da empresa.
 *
 * @property int         $id
 * @property string|null $codigo
 * @property string      $nome
 * @property bool        $ativo
 */
class Projeto extends Model
{

    protected $table = 'produtividade_projeto';

    public function getTable()
    {
        return config('erp.tabelas.projeto', 'produtividade_projeto');
    }

    protected $fillable = [
        'codigo',
        'nome',
        'ativo',
        'codigo_cliente_id',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope para buscar apenas projetos ativos.
     * Equivale ao queryset Projeto.objects.filter(ativo=True) do Django.
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Alias plural para manter consistência com outros models (ex: Colaborador).
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'projeto_id');
    }

    /**
     * Gestores vinculados a este projeto.
     */
    public function gestores(): BelongsToMany
    {
        return $this->belongsToMany(
            Colaborador::class,
            'colaborador_projeto_gerenciado',
            'projeto_id',
            'colaborador_id'
        )->withTimestamps();
    }

    /**
     * Cliente vinculado a este projeto.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(CodigoCliente::class, 'codigo_cliente_id');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django: "{codigo} - {nome}"
     */
    public function __toString(): string
    {
        return $this->codigo
            ? "{$this->codigo} - {$this->nome}"
            : $this->nome;
    }
}
