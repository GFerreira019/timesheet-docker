<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent equivalente ao model Django: LogAuditoria
 *
 * Tabela central de auditoria — rastreia TODAS as ações críticas do sistema.
 * Design APPEND-ONLY: registros nunca são alterados após criação.
 *
 * ACAO_CHOICES:
 * - LOGIN / LOGOUT / LOGIN_FALHA (via Listener de events do Laravel Auth)
 * - CRIACAO / EDICAO / EXCLUSAO  (via Controllers)
 * - APROVACAO / REJEICAO / SOLICITACAO / APROVACAO_AJUSTE (via fluxo de workflow)
 * - EXPORTACAO (via RelatorioController)
 *
 * @property int         $id
 * @property int|null    $user_id        null = ação do sistema (ex: aprovação automática)
 * @property string      $acao
 * @property string      $modelo_afetado
 * @property string|null $objeto_id
 * @property string|null $detalhes
 * @property string|null $ip_address
 * @property string      $data_hora      auto_now_add equivalente
 */
class LogAuditoria extends Model
{
    protected $table = 'log_auditorias';

    /**
     * Logs são imutáveis — não usamos updated_at.
     * created_at mapeado para data_hora.
     */
    const CREATED_AT = 'data_hora';
    const UPDATED_AT = null;

    public $timestamps = true;

    /** @var array<string, string> */
    public const ACAO_CHOICES = [
        'LOGIN'            => 'Login / Acesso',
        'LOGOUT'           => 'Logout / Saída',
        'LOGIN_FALHA'      => 'Falha de Login',
        'CRIACAO'          => 'Criação de Registro',
        'EDICAO'           => 'Edição de Registro',
        'EXCLUSAO'         => 'Exclusão de Registro',
        'APROVACAO'        => 'Aprovação de Apontamento',
        'REJEICAO'         => 'Rejeição de Apontamento',
        'RESPOSTA'         => 'Resposta do Colaborador',
        'SOLICITACAO'      => 'Solicitação de Ajuste',
        'APROVACAO_AJUSTE' => 'Aprovação de Ajuste',
        'EXPORTACAO'       => 'Exportação de Dados',
    ];

    protected $fillable = [
        'user_id',
        'acao',
        'modelo_afetado',
        'objeto_id',
        'detalhes',
        'ip_address',
        'data_hora',
    ];

    protected $casts = [
        'data_hora' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Usuário responsável pela ação.
     * Equivalente ao usuario = ForeignKey(User, on_delete=SET_NULL, null=True).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django:
     * "[{data_hora dd/MM HH:mm}] {username} - {acao}"
     */
    public function __toString(): string
    {
        $dataFormatada = $this->data_hora
            ? $this->data_hora->format('d/m H:i')
            : '--/-- --:--';

        $usuarioStr = $this->user?->name ?? 'Usuário Removido/Sistema';

        return "[{$dataFormatada}] {$usuarioStr} - {$this->acao}";
    }
}
