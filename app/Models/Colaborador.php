<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model Eloquent equivalente ao model Django: Colaborador
 *
 * Entidade central que estende o User do Laravel com regras de negócio.
 * Mantém a relação OneToOne com a tabela users (equivalente ao user_account).
 *
 * RBAC (Controle de Acesso):
 * - Owner/Superuser: users.is_superuser = true
 * - Grupos (GESTOR, ADMINISTRATIVO, COORDENADOR): gerenciados pelo
 *   pacote spatie/laravel-permission, instalado na Etapa 2.
 *
 * @property int         $id
 * @property string      $id_colaborador
 * @property string      $nome_completo
 * @property string      $cargo
 * @property string|null $cidade
 * @property string|null $uf
 * @property int|null    $user_id
 * @property int|null    $setor_id
 * @property string|null $telefone
 * @property-read string|null $solides_id  Lido via accessor de users.solides_id
 */
class Colaborador extends Model
{
    public $dataVigenciaVirtual = null; // Atributo virtual para o Observer ler

    protected $table = 'produtividade_colaborador';

    public function getTable()
    {
        return config('erp.tabelas.colaborador', 'produtividade_colaborador');
    }

    protected $fillable = [
        'id_colaborador',
        'nome_completo',
        'nivel_acesso',
        'cargo',
        'cidade_moradia',
        'cidade_trabalho',
        'uf',
        'setor',
        'setor_id',
        'telefone',
        'data_admissao',
        'data_demissao',
        'data_vigencia',
    ];

    // -------------------------------------------------------------------------
    // Accessor: solides_id — delega para users.solides_id
    // -------------------------------------------------------------------------
    // O campo foi centralizado na tabela users para ser a fonte de verdade da
    // integração com o ERP. Este accessor mantém a API pública do Colaborador
    // compatível: $colab->solides_id continua funcionando sem alterar os
    // controllers, SolidesService ou qualquer outro código existente.
    public function getSolidesIdAttribute(): ?string
    {
        return $this->user?->solides_id;
    }

    protected $casts = [
    ];

    /**
     * Intercepta a data de admissão para nunca dar erro, independentemente do formato no banco.
     */
    protected function dataAdmissao(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
                }
            },
            set: function ($value) {
                if (!$value) return null;
                return str_contains($value, '/') 
                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d')
                    : \Carbon\Carbon::parse($value)->format('Y-m-d');
            }
        );
    }

    /**
     * Intercepta a data de demissão para nunca dar erro, independentemente do formato no banco.
     */
    protected function dataDemissao(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value) return null;
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
                }
            },
            set: function ($value) {
                if (!$value) return null;
                return str_contains($value, '/') 
                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d')
                    : \Carbon\Carbon::parse($value)->format('Y-m-d');
            }
        );
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Conta de usuário vinculada (HasOne: a FK está em users.produtividade_colaborador_id).
     * Acessado via: $colaborador->user
     */
    public function user(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'produtividade_colaborador_id');
    }

    /**
     * Setor de alocação do colaborador.
     * Equivalente ao setor = ForeignKey(Setor, on_delete=SET_NULL) do Django.
     */
    public function setorRelacionamento(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_id');
    }

    /**
     * Setores que este colaborador gerencia (papel de Gestor).
     * Equivalente ao setores_gerenciados = ManyToManyField(Setor, related_name='gestores').
     */
    public function setoresGerenciados(): BelongsToMany
    {
        return $this->belongsToMany(
            Setor::class,
            'colaborador_setor_gerenciado',
            'colaborador_id',
            'setor_id'
        );
    }

    /**
     * Setores vinculados ao colaborador (Nível GERENCIAL e SAC).
     */
    public function setoresVinculados(): BelongsToMany
    {
        return $this->belongsToMany(
            Setor::class,
            'colaborador_setor_vinculo',
            'colaborador_id',
            'setor_id'
        )->withTimestamps();
    }

    /**
     * Obras/Projetos que este colaborador gerencia (papel de Gestor).
     */
    public function projetosGerenciados(): BelongsToMany
    {
        return $this->belongsToMany(
            Projeto::class,
            'colaborador_projeto_gerenciado',
            'colaborador_id',
            'projeto_id'
        );
    }

    /**
     * Clientes que este colaborador gerencia (através de projetos).
     */
    public function clientesGerenciados(): BelongsToMany
    {
        return $this->belongsToMany(
            CodigoCliente::class,
            'colaborador_cliente_gerenciado',
            'colaborador_id',
            'codigo_cliente_id'
        )->withTimestamps();
    }

    /**
     * Apontamentos onde este colaborador é o titular.
     * Equivalente ao colaborador = ForeignKey(Colaborador, on_delete=PROTECT).
     */
    public function apontamentos(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'colaborador_id');
    }

    /**
     * Pontos importados da Sólides (Espelho de Ponto).
     */
    public function pontosSolides(): HasMany
    {
        return $this->hasMany(SolidesPonto::class, 'colaborador_id');
    }

    /**
     * Apontamentos onde este colaborador é auxiliar principal.
     * Equivalente ao related_name='apontamentos_auxiliados' do Django.
     */
    public function apontamentosAuxiliados(): HasMany
    {
        return $this->hasMany(Apontamento::class, 'auxiliar_id');
    }

    /**
     * Apontamentos onde este colaborador aparece como auxiliar extra.
     * Equivalente ao related_name='apontamentos_como_extra' do Django.
     */
    public function apontamentosComoExtra(): BelongsToMany
    {
        return $this->belongsToMany(
            Apontamento::class,
            'apontamento_auxiliar_extra',
            'colaborador_id',
            'apontamento_id'
        );
    }

    /**
     * Notificações recebidas por este colaborador.
     * Equivalente ao related_name='notificacoes' do Django.
     */
    public function notificacoes(): HasMany
    {
        return $this->hasMany(Notificacao::class, 'colaborador_id');
    }

    /**
     * Histórico de auditoria de alterações do colaborador.
     */
    public function historicos(): HasMany
    {
        return $this->hasMany(ColaboradorHistorico::class, 'colaborador_id');
    }

    // -------------------------------------------------------------------------
    // Helpers de Permissão (delegam para o User)
    // -------------------------------------------------------------------------

    /**
     * Verifica se este colaborador é superusuário (admin).
     */
    public function isAdmin(): bool
    {
        return $this->user?->is_superuser ?? false;
    }

    /**
     * Verifica se pertence a um grupo (role) específico.
     * Equivalente ao check_group(user, group_name) do utils.py.
     * Requer spatie/laravel-permission (Etapa 2).
     */
    public function isInGroup(string $groupName): bool
    {
        return $this->user?->hasRole($groupName) ?? false;
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Equivalente ao __str__ do Django: "{nome_completo}"
     */
    public function __toString(): string
    {
        return $this->nome_completo;
    }

    /**
     * Retorna o nome completo concatenado com (DESLIGADO) se o colaborador estiver inativo.
     */
    public function getNomeExibicaoAttribute(): string
    {
        if ($this->data_demissao && $this->data_demissao !== '0000-00-00') {
            return $this->nome_completo . ' (DESLIGADO)';
        }
        return $this->nome_completo;
    }

    /**
     * Escopo para listar apenas colaboradores ativos.
     *
     * Compatível com PostgreSQL: colunas do tipo DATE não aceitam comparação
     * com string vazia ('') ou '0000-00-00' — apenas NULL ou datas reais.
     *
     * Regra:
     *  - data_demissao IS NULL  → ativo (sem data de saída cadastrada)
     *  - data_demissao > hoje   → demissão agendada para o futuro, ainda ativo
     */
    public function scopeAtivos($query)
    {
        return $query->whereNull('data_demissao');
    }
}
