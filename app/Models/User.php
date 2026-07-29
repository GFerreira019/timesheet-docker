<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'password',
        'is_superuser',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_superuser'      => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Perfil de colaborador vinculado a esta conta.
     * Equivalente ao user_account = OneToOneField(User) do model Colaborador.
     * Acessado via: $user->colaborador
     */
    public function colaborador(): HasOne
    {
        return $this->hasOne(Colaborador::class, 'user_id');
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
