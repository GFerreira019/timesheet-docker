<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * AcessoHelper — Funções de controle de acesso (RBAC)
 *
 * Equivalente direto ao módulo utils.py do Django:
 *
 * Django utils.py          → AcessoHelper.php
 * ─────────────────────────────────────────────
 * is_owner(user)           → AcessoHelper::isOwner()
 * check_group(user, name)  → AcessoHelper::checkGroup(name)
 * is_coordenador(user)     → AcessoHelper::isCoordenador()
 * is_administrativo(user)  → AcessoHelper::isAdministrativo()
 * is_gerente(user)         → AcessoHelper::isGerente()
 * pode_fazer_rateio(user)  → AcessoHelper::podeFazerRateio()
 *
 * Uso nos Controllers:
 *   AcessoHelper::isOwner()          → verifica usuário autenticado
 *   AcessoHelper::isOwner($user)     → verifica usuário específico
 */
class AcessoHelper
{
    // -------------------------------------------------------------------------
    // Grupos (equivalente aos Django Groups)
    // -------------------------------------------------------------------------

    public const GRUPO_GESTOR          = 'GESTOR';
    public const GRUPO_ADMINISTRATIVO  = 'ADMINISTRATIVO';
    public const GRUPO_COORDENADOR     = 'COORDENADOR';

    /** Lista de todos os grupos (roles) do sistema — usado no RolesSeeder */
    public const GRUPOS = [
        self::GRUPO_GESTOR,
        self::GRUPO_ADMINISTRATIVO,
        self::GRUPO_COORDENADOR,
    ];

    /** Cargos que são isentos de meta diária (ControlePontoService) */
    public const CARGOS_ISENTOS = [
        'JOVEM APRENDIZ',
        'GERENTE',
        'CONTROLLER',
        'DIRETOR',
        'SÓCIO',
    ];

    // -------------------------------------------------------------------------
    // Métodos de verificação RBAC
    // -------------------------------------------------------------------------

    /**
     * Resolve o usuário: se não informado, usa o autenticado.
     */
    private static function resolveUser(?User $user = null): ?User
    {
        return $user ?? Auth::user();
    }

    /**
     * Verifica se o usuário é Owner (superusuário).
     * Equivalente a: is_owner(user) → return user.is_superuser
     */
    public static function isOwner(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->isOwner() ?? false;
    }

    /**
     * Verifica se o usuário pertence a um grupo (role).
     * Usa estritamente spatie/laravel-permission.
     */
    public static function checkGroup(string $groupName, ?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole($groupName) ?? false;
    }

    /**
     * Verifica se é Gerente/Gestor.
     */
    public static function isGerente(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole('GESTOR') ?? false;
    }

    /**
     * Verifica se é Administrativo.
     */
    public static function isAdministrativo(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole('ADMINISTRATIVO') ?? false;
    }

    /**
     * Verifica se é Coordenador.
     */
    public static function isCoordenador(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole('COORDENADOR') ?? false;
    }

    /**
     * Verifica se pode fazer rateio de obras.
     */
    public static function podeFazerRateio(?User $user = null): bool
    {
        return self::isCoordenador($user) || self::isAdministrativo($user) || self::isOwner($user);
    }

    /**
     * Verifica se o usuário tem perfil GERENCIAL.
     */
    public static function isGerencial(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole('GERENCIAL') ?? false;
    }

    /**
     * Verifica se o usuário tem perfil SAC.
     */
    public static function isSac(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole('SAC') ?? false;
    }

    /**
     * Verifica se o usuário é Administrador.
     */
    public static function isAdmin(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasRole('ADMIN') || self::isOwner($user);
    }

    /**
     * Verifica se o usuário tem acesso expandido de setor (GERENCIAL ou SAC).
     */
    public static function isAcessoExpandido(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->hasAnyRole(['GERENCIAL', 'SAC']) ?? false;
    }

    /**
     * Verifica se o usuário é estritamente Operacional (trabalhador padrão sem cargos extras).
     */
    public static function isOperacional(?User $user = null): bool
    {
        return !self::isAdmin($user) && !self::isGerente($user) && !self::isAdministrativo($user) 
            && !self::isAcessoExpandido($user) && !self::isCoordenador($user) && !self::isOwner($user);
    }

    /**
     * Verifica se o usuário tem permissão para lançar apontamentos em nome de terceiros.
     */
    public static function podeLancarPorTerceiros(?User $user = null): bool
    {
        return self::isAdmin($user) || self::isAcessoExpandido($user);
    }

    /**
     * Verifica se o cargo do colaborador está isento de meta diária.
     * Equivalente à lista cargos_isentos do ControlePontoService do Django.
     */
    public static function isCargoIsento(string $cargo): bool
    {
        $cargoUpper = strtoupper($cargo);
        foreach (self::CARGOS_ISENTOS as $isento) {
            if (str_contains($cargoUpper, $isento)) {
                return true;
            }
        }
        return false;
    }
}
