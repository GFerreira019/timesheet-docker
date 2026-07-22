<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Eloquent equivalente ao model Django: Setor
 *
 * Cadastro de departamentos/setores para controle de lotação e permissões de acesso.
 *
 * @property int    $id
 * @property string $nome
 * @property bool   $ativo
 */
class Setor extends Model
{
    protected $table = 'setores';

    protected $fillable = [
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope para buscar apenas setores ativos.
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Colaboradores alocados neste setor.
     * Equivalente ao ForeignKey(Setor) no model Colaborador do Django.
     */
    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'setor_id');
    }

    /**
     * Gestores responsáveis por este setor.
     * Equivalente ao related_name='gestores' do campo setores_gerenciados no Django.
     */
    public function gestores(): BelongsToMany
    {
        return $this->belongsToMany(
            Colaborador::class,
            'colaborador_setor_gerenciado',
            'setor_id',
            'colaborador_id'
        );
    }

    /**
     * Colaboradores vinculados a este setor (Nível GERENCIAL e SAC).
     */
    public function colaboradoresVinculados(): BelongsToMany
    {
        return $this->belongsToMany(
            Colaborador::class,
            'colaborador_setor_vinculo',
            'setor_id',
            'colaborador_id'
        )->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django.
     */
    public function __toString(): string
    {
        return $this->nome;
    }
}
