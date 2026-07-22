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
     * Equivalente a: check_group(user, group_name)
     * Usa spatie/laravel-permission via $user->hasRole().
     */
    public static function checkGroup(string $groupName, ?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->checkGroup($groupName) ?? false;
    }

    /**
     * Verifica se é Gerente/Gestor.
     * Equivalente a: is_gerente(user) → check_group('GESTOR') or is_owner()
     */
    public static function isGerente(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->isGerente() ?? false;
    }

    /**
     * Verifica se é Administrativo.
     * Equivalente a: is_administrativo(user) → check_group('ADMINISTRATIVO') or is_owner()
     */
    public static function isAdministrativo(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->isAdministrativo() ?? false;
    }

    /**
     * Verifica se é Coordenador.
     * Equivalente a: is_coordenador(user) → check_group('COORDENADOR') or is_owner()
     */
    public static function isCoordenador(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->isCoordenador() ?? false;
    }

    /**
     * Verifica se pode fazer rateio de obras.
     * Equivalente a: pode_fazer_rateio(user)
     * → is_coordenador() or is_administrativo() or is_owner()
     */
    public static function podeFazerRateio(?User $user = null): bool
    {
        $u = self::resolveUser($user);
        return $u?->podeFazerRateio() ?? false;
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
