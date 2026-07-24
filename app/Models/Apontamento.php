<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent equivalente ao model Django: Apontamento
 *
 * Registro principal de Timesheet — tabela CORE do sistema.
 *
 * Constantes de CHOICES (equivalentes aos choices do Django):
 * - LOCAL_CHOICES: INT | EXT
 * - STATUS_APROVACAO_CHOICES: EM_ANALISE | APROVADO | REJEITADO | SOLICITACAO_AJUSTE
 * - STATUS_AJUSTE_CHOICES: PENDENTE | APROVADO
 *
 * @property int         $id
 * @property int         $colaborador_id
 * @property string      $data_apontamento
 * @property string      $hora_inicio
 * @property string|null $hora_termino
 * @property string      $local_execucao
 * @property int|null    $projeto_id
 * @property int|null    $codigo_cliente_id
 * @property int|null    $centro_custo_id
 * @property int|null    $veiculo_id
 * @property string|null $veiculo_manual_modelo
 * @property string|null $veiculo_manual_placa
 * @property string|null $ocorrencias
 * @property int|null    $auxiliar_id
 * @property bool        $em_plantao
 * @property string|null $data_plantao
 * @property bool        $dorme_fora
 * @property string|null $data_dorme_fora
 * @property int|null    $registrado_por_id
 * @property string      $data_registro
 * @property string|null $id_agrupamento
 * @property string|null $motivo_ajuste
 * @property string      $status_aprovacao
 * @property string|null $status_ajuste
 * @property int         $contagem_edicao
 * @property string|null $motivo_rejeicao
 * @property float|null  $latitude
 * @property float|null  $longitude
 * @property bool        $flag_atencao
 * @property string|null $motivo_alerta
 */
class Apontamento extends Model
{
    protected $table = 'apontamentos';

    // -------------------------------------------------------------------------
    // CHOICES (equivalentes ao Django)
    // -------------------------------------------------------------------------

    /** @var array<string, string> */
    public const LOCAL_CHOICES = [
        'EXTERNO' => 'Dentro da obra (em campo)',
        'INTERNO' => 'Fora da obra (na base)',
    ];

    /** @var array<string, string> */
    public const STATUS_APROVACAO_CHOICES = [
        'EM_ANALISE'        => 'Em Análise',
        'APROVADO'          => 'Aprovado',
        'REJEITADO'         => 'Rejeitado',
        'SOLICITACAO_AJUSTE'=> 'Solicitação de Ajuste',
    ];

    /** @var array<string, string> */
    public const STATUS_AJUSTE_CHOICES = [
        'PENDENTE' => 'Pendente',
        'APROVADO' => 'Aprovado',
    ];

    // -------------------------------------------------------------------------
    // Configuração do Model
    // -------------------------------------------------------------------------

    protected $fillable = [
        'colaborador_id',
        'data_apontamento',
        'hora_inicio',
        'hora_termino',
        'local_execucao',
        'projeto_id',
        'codigo_cliente_id',
        'centro_custo_id',
        'veiculo_id',
        'veiculo_manual_modelo',
        'veiculo_manual_placa',
        'ocorrencias',
        'auxiliar_id',
        'em_plantao',
        'data_plantao',
        'dorme_fora',
        'data_dorme_fora',
        'registrado_por_id',
        'data_registro',
        'id_agrupamento',
        'motivo_ajuste',
        'status_aprovacao',
        'status_ajuste',
        'contagem_edicao',
        'motivo_rejeicao',
        'latitude',
        'longitude',
        'flag_atencao',
        'motivo_alerta',
        'tipo_aprovacao',
        'aprovador_id',
        'data_aprovacao',
    ];

    protected $casts = [
        'data_apontamento' => 'date',
        'data_plantao'     => 'date',
        'data_dorme_fora'  => 'date',
        'data_registro'    => 'datetime',
        'em_plantao'       => 'boolean',
        'dorme_fora'       => 'boolean',
        'flag_atencao'     => 'boolean',
        'latitude'         => 'decimal:8',
        'longitude'        => 'decimal:8',
        'contagem_edicao'  => 'integer',
        'data_aprovacao'   => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Colaborador titular do apontamento. */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    /** Usuário que aprovou o apontamento manualmente (null = aprovação automática). */
    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'aprovador_id');
    }

    /** Projeto/Obra associado. */
    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class, 'projeto_id');
    }

    /** Código do cliente associado. */
    public function codigoCliente(): BelongsTo
    {
        return $this->belongsTo(CodigoCliente::class, 'codigo_cliente_id');
    }

    /** Centro de custo / justificativa. */
    public function centroCusto(): BelongsTo
    {
        return $this->belongsTo(CentroCusto::class, 'centro_custo_id');
    }

    /** Veículo cadastrado da frota. */
    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    /**
     * Auxiliar principal (FK simples para Colaborador).
     * Equivalente ao related_name='apontamentos_auxiliados' do Django.
     */
    public function auxiliar(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'auxiliar_id');
    }

    /**
     * Auxiliares extras (M2M).
     * Equivalente ao auxiliares_extras = ManyToManyField(Colaborador, related_name='apontamentos_como_extra').
     */
    public function auxiliaresExtras(): BelongsToMany
    {
        return $this->belongsToMany(
            Colaborador::class,
            'apontamento_auxiliar_extra',
            'apontamento_id',
            'colaborador_id'
        );
    }

    /**
     * Usuário que registrou o apontamento.
     * Equivalente ao registrado_por = ForeignKey(User, ...).
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'registrado_por_id');
    }

    /**
     * Versões históricas (snapshots) deste apontamento.
     * Equivalente ao related_name='historico_versoes' do Django.
     */
    public function historicoVersoes(): HasMany
    {
        return $this->hasMany(ApontamentoHistorico::class, 'apontamento_original_id')
            ->orderByDesc('numero_edicao');
    }

    // -------------------------------------------------------------------------
    // Accessors (equivalentes a @property do Django)
    // -------------------------------------------------------------------------

    /**
     * Calcula a duração formatada HH:MM considerando virada de dia (overnight).
     * Equivalente exato ao @property duracao_total_str do model Django.
     */
    public function getDuracaoTotalStrAttribute(): string
    {
        if (!$this->hora_inicio || !$this->hora_termino) {
            return '00:00';
        }

        $baseDate = '2000-01-01';
        $inicio   = \Carbon\Carbon::parse("{$baseDate} {$this->hora_inicio}");
        $termino  = \Carbon\Carbon::parse("{$baseDate} {$this->hora_termino}");

        // Trata virada de meia-noite (overnight)
        if ($termino->lt($inicio)) {
            $termino->addDay();
        }

        $diffSeconds = abs($termino->diffInSeconds($inicio));
        $h = (int) ($diffSeconds / 3600);
        $m = (int) (($diffSeconds % 3600) / 60);

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Calcula a duração em segundos — usado pelo ConformidadeCLTService.
     */
    public function getDuracaoEmSegundosAttribute(): int
    {
        if (!$this->hora_inicio || !$this->hora_termino) {
            return 0;
        }

        $base    = '2000-01-01';
        $inicio  = \Carbon\Carbon::parse("{$baseDate} {$this->hora_inicio}");
        $termino = \Carbon\Carbon::parse("{$baseDate} {$this->hora_termino}");

        if ($termino->lt($inicio)) {
            $termino->addDay();
        }

        return (int) abs($termino->diffInSeconds($inicio));
    }

    /**
     * Verifica se este apontamento está em aberto (Check-in ativo).
     * hora_termino = NULL indica cronômetro rodando.
     */
    public function getEmAndamentoAttribute(): bool
    {
        return is_null($this->hora_termino);
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django: "{colaborador} - {data_apontamento}"
     */
    public function __toString(): string
    {
        return "{$this->colaborador?->nome_completo} - {$this->data_apontamento}";
    }

    // -------------------------------------------------------------------------
    // Scopes (RLS)
    // -------------------------------------------------------------------------

    /**
     * Scope para Row-Level Security baseado em vínculos diretos (Data-Driven)
     * Abandona-se a dependência estrita de roles Spatie para a visão de Gestor/Administrativo.
     */
    public function scopeVisibilidadePermitida($query, $user)
    {
        $colaborador = $user->colaborador;
        $nivel = $colaborador ? $colaborador->nivel_acesso : 'OPERACIONAL';

        // 1. ADMIN
        if ($nivel === 'ADMIN' || (method_exists($user, 'isOwner') && $user->isOwner())) {
            return $query;
        }

        // 2. GESTOR
        if ($nivel === 'GESTOR') {
            $projetosIds = $colaborador->projetosGerenciados()->pluck('produtividade_projeto.id')->toArray();
            $clientesIds = $colaborador->clientesGerenciados()->pluck('produtividade_codigocliente.id')->toArray();

            return $query->where(function ($q) use ($user, $colaborador, $projetosIds, $clientesIds) {
                // Acesso aos seus próprios apontamentos
                $q->where('registrado_por_id', $user->id)
                  ->orWhere('colaborador_id', $colaborador->id);

                // Acesso aos apontamentos das obras/clientes gerenciados
                if (!empty($projetosIds)) {
                    $q->orWhereIn('projeto_id', $projetosIds);
                }
                if (!empty($clientesIds)) {
                    $q->orWhereHas('projeto', function($subQ) use ($clientesIds) {
                        $subQ->whereIn('codigo_cliente_id', $clientesIds);
                    });
                }
            });
        }

        // 3. GERENCIAL / SAC
        if (in_array($nivel, ['GERENCIAL', 'SAC'])) {
            $setoresVinculadosIds = $colaborador->setoresVinculados()->pluck('setores.id')->toArray();

            return $query->where(function ($q) use ($user, $colaborador, $setoresVinculadosIds) {
                // Acesso aos seus próprios apontamentos
                $q->where('registrado_por_id', $user->id)
                  ->orWhere('colaborador_id', $colaborador->id);

                // Acesso aos apontamentos dos colaboradores dos setores vinculados
                if (!empty($setoresVinculadosIds)) {
                    $q->orWhereHas('colaborador', function($subQ) use ($setoresVinculadosIds) {
                        $subQ->whereIn('setor_id', $setoresVinculadosIds);
                    });
                }
            });
        }

        // 4. OPERACIONAL
        return $query->where(function ($q) use ($user, $colaborador) {
            $q->where('registrado_por_id', $user->id);
            if ($colaborador) {
                $q->orWhere('colaborador_id', $colaborador->id);
            }
        });
    }
}
