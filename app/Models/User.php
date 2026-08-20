<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Model User do Laravel estendido com campos e helpers do sistema Timesheet.
 *
 * Equivalências Django → Laravel:
 * - user.is_superuser → $user->is_superuser (bool, campo na tabela users)
 * - user.groups       → roles via spatie/laravel-permission (HasRoles trait)
 * - user.colaborador  → $user->colaborador (HasOne)
 *
 * Helpers RBAC replicados de utils.py:
 * - is_owner()         → isOwner()
 * - is_gerente()       → isGerente()
 * - is_coordenador()   → isCoordenador()
 * - is_administrativo() → isAdministrativo()
 * - pode_fazer_rateio() → podeFazerRateio()
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'produtividade_colaborador_id',
        'id_usuario_erp',
        // ID da plataforma Sólides — centralizado aqui para ser a fonte de verdade
        // da integração de ponto. O Colaborador lê este valor via accessor.
        'solides_id',
        'ignorado_erp',
        'fcm_token',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_superuser'      => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Perfil de colaborador vinculado a esta conta via FK direta.
     * Acessado via: $user->colaborador
     */
    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'produtividade_colaborador_id');
    }

    /**
     * Alias semântico para o relacionamento com o perfil de produtividade.
     */
    public function produtividade()
    {
        return $this->belongsTo(Colaborador::class, 'produtividade_colaborador_id');
    }

    /**
     * Registros de ponto sincronizados da Sólides para este usuário.
     *
     * A ligação é feita pela coluna `solides_id` desta tabela (users)
     * e a coluna `colaborador_id` na tabela `solides_pontos` (que aponta
     * para produtividade_colaborador). O acesso direto deve ser feito
     * preferencialmente via $user->colaborador->pontosSolides().
     * Este relacionamento é fornecido como atalho para relatórios.
     */
    public function pontosSolides(): HasMany
    {
        // Liga: users.id → produtividade_colaborador.user_id
        // e depois produtividade_colaborador.id → solides_pontos.colaborador_id
        // Como a FK direta é users → produtividade_colaborador via produtividade_colaborador_id,
        // este atalho usa hasManyThrough:
        return $this->hasManyThrough(
            \App\Models\SolidesPonto::class,
            Colaborador::class,
            'id',                       // FK em produtividade_colaborador que aponta para users
            'colaborador_id',           // FK em solides_pontos que aponta para produtividade_colaborador
            'produtividade_colaborador_id', // coluna local em users
            'id'                        // PK em produtividade_colaborador
        );
    }

    /**
     * Logs de auditoria gerados por este usuário.
     */
    public function logAuditorias(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Helpers de RBAC — equivalentes às funções de utils.py
    // -------------------------------------------------------------------------

    /**
     * Verifica se o usuário é Owner (superusuário).
     * Equivalente a is_owner(user) do utils.py: return user.is_superuser
     */
    public function isOwner(): bool
    {
        return (bool) $this->is_superuser;
    }

    /**
     * Verifica se o usuário pertence a um grupo (role).
     * Equivalente a check_group(user, group_name) do utils.py.
     * Requer spatie/laravel-permission (Etapa 2).
     * Fallback sem o pacote instalado: retorna false.
     */
    public function checkGroup(string $groupName): bool
    {
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole($groupName);
        }
        return false;
    }

    /**
     * Equivalente a is_gerente(user) do utils.py:
     * return check_group(user, 'GESTOR') or is_owner(user)
     */
    public function isGerente(): bool
    {
        return $this->checkGroup('GESTOR') || $this->isOwner();
    }

    /**
     * Equivalente a is_administrativo(user) do utils.py:
     * return check_group(user, 'ADMINISTRATIVO') or is_owner(user)
     */
    public function isAdministrativo(): bool
    {
        return $this->checkGroup('ADMINISTRATIVO') || $this->isOwner();
    }

    /**
     * Equivalente a is_coordenador(user) do utils.py:
     * return check_group(user, 'COORDENADOR') or is_owner(user)
     */
    public function isCoordenador(): bool
    {
        return $this->checkGroup('COORDENADOR') || $this->isOwner();
    }

    /**
     * Equivalente a pode_fazer_rateio(user) do utils.py:
     * return is_coordenador(user) or is_administrativo(user) or is_owner(user)
     */
    public function podeFazerRateio(): bool
    {
        return $this->isCoordenador() || $this->isAdministrativo() || $this->isOwner();
    }

    /**
     * Verifica se o usuário tem perfil GERENCIAL.
     * Perfil com visão de setor, mas sem poderes de aprovação de gestor.
     */
    public function isGerencial(): bool
    {
        return $this->checkGroup('GERENCIAL') || $this->isOwner();
    }

    /**
     * Verifica se o usuário tem perfil SAC.
     * Perfil com acesso limitado a setores vinculados (suporte/atendimento).
     */
    public function isSac(): bool
    {
        return $this->checkGroup('SAC') || $this->isOwner();
    }

    /**
     * Verifica se o usuário tem perfil ADMIN (administrador do sistema).
     * Admin tem visão total igual ao Owner, mas sem o flag is_superuser.
     */
    public function isAdmin(): bool
    {
        return $this->checkGroup('ADMIN') || $this->isOwner();
    }

    /**
     * Verifica se o usuário tem acesso expandido (visão de setor).
     * Equivale a: GERENCIAL ou SAC ou Owner.
     */
    public function isAcessoExpandido(): bool
    {
        return $this->isGerencial() || $this->isSac() || $this->isOwner();
    }
}
