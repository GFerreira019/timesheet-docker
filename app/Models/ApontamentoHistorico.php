<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent equivalente ao model Django: ApontamentoHistorico
 *
 * Armazena o estado anterior (snapshot JSON) de um apontamento antes de ser editado.
 * Permite que o Gestor compare a versão original com a editada (Diff visual).
 * Usado na view aprovacao_analise.
 *
 * @property int    $id
 * @property int    $apontamento_original_id
 * @property array  $dados_snapshot          JSON decodificado automaticamente
 * @property int|null $editado_por_id
 * @property string $data_edicao
 * @property int    $numero_edicao
 */
class ApontamentoHistorico extends Model
{
    protected $table = 'apontamento_historicos';

    /**
     * Esta tabela não usa updated_at — snapshots são imutáveis.
     */
    public $timestamps = false;

    protected $fillable = [
        'apontamento_original_id',
        'dados_snapshot',
        'editado_por_id',
        'data_edicao',
        'numero_edicao',
    ];

    protected $casts = [
        'dados_snapshot' => 'array',   // JSON → array PHP automaticamente
        'data_edicao'    => 'datetime',
        'numero_edicao'  => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Apontamento original ao qual pertence este histórico.
     * Equivalente ao apontamento_original = ForeignKey(Apontamento, on_delete=CASCADE).
     */
    public function apontamentoOriginal(): BelongsTo
    {
        return $this->belongsTo(Apontamento::class, 'apontamento_original_id');
    }

    /**
     * Usuário que realizou a edição.
     * Equivalente ao editado_por = ForeignKey(User, on_delete=SET_NULL).
     */
    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'editado_por_id');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django: "V{numero_edicao} - {apontamento_original}"
     */
    public function __toString(): string
    {
        return "V{$this->numero_edicao} - {$this->apontamentoOriginal}";
    }
}
