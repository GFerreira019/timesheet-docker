<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorHistorico extends Model
{
    protected $fillable = [
        'colaborador_id',
        'user_id_alteracao',
        'dados_anteriores',
        'campos_alterados',
        'data_vigencia',
    ];

    protected $casts = [
        'dados_anteriores' => 'array',
        'campos_alterados' => 'array',
        'data_vigencia' => 'date',
    ];

    /**
     * O colaborador ao qual este histórico pertence.
     */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    /**
     * O usuário responsável por realizar a alteração (se aplicável).
     */
    public function usuarioAlteracao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_alteracao');
    }
}
