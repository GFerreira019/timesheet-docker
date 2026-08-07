<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent equivalente ao model Django: Notificacao
 *
 * Armazena alertas e mensagens do sistema para o colaborador.
 * O Owner vê as RESPOSTAS dos colaboradores; colaboradores veem seus alertas.
 *
 * TIPO_CHOICES:
 * - ALERTA: Alerta de Conformidade (pendência de horas, etc.)
 * - INFO:   Informativo Geral (avisos manuais do Owner)
 * - SUCESSO: Conclusão de Processo
 *
 * @property int         $id
 * @property int         $colaborador_id
 * @property string      $titulo
 * @property string      $mensagem
 * @property string      $tipo
 * @property bool        $lida
 * @property \Carbon\Carbon $created_at
 * @property string|null $data_referencia
 * @property string|null $comentario_colaborador   Resposta do colaborador (visível ao Owner)
 */
class Notificacao extends Model
{
    protected $table = 'notificacoes';

    /** @var array<string, string> */
    public const TIPO_CHOICES = [
        'ALERTA'  => 'Alerta de Conformidade',
        'INFO'    => 'Informativo Geral',
        'SUCESSO' => 'Conclusão de Processo',
    ];

    /**
     * Usa as colunas padrão created_at e updated_at geradas por timestamps().
     * Isso corrige o SQLSTATE[42703] no PostgreSQL ao ordenar por created_at.
     */
    // CREATED_AT e UPDATED_AT usam os nomes padrão do Laravel (created_at / updated_at)

    protected $fillable = [
        'colaborador_id',
        'titulo',
        'mensagem',
        'tipo',
        'lida',
        'data_referencia',
        'comentario_colaborador',
        'remetente_id',
        'apontamento_id',
    ];

    protected $casts = [
        // boolean explícito garante compat. com PostgreSQL (evita int vs bool)
        'lida'            => 'boolean',
        'data_referencia' => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Colaborador destinatário.
     * Equivalente ao colaborador = ForeignKey(Colaborador, on_delete=CASCADE).
     */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    /**
     * Usuário que gerou a notificação.
     */
    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remetente_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Filtra apenas notificações não lidas. */
    public function scopeNaoLidas($query)
    {
        return $query->where('lida', false);
    }

    /** Filtra notificações que possuem resposta do colaborador. */
    public function scopeComResposta($query)
    {
        return $query->whereNotNull('comentario_colaborador')
            ->where('comentario_colaborador', '!=', '');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django:
     * "{colaborador.nome_completo} - {titulo}"
     */
    public function __toString(): string
    {
        return "{$this->colaborador?->nome_completo} - {$this->titulo}";
    }
}
