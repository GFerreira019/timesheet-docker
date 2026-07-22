<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * Aliases de middleware customizados para o sistema de RBAC.
         *
         * Equivalentes aos decorators Django:
         *   @user_passes_test(is_owner)   → middleware('owner')
         *   @user_passes_test(is_gerente) → middleware('gerente')
         *
         * Spatie Permission (grupos GESTOR/ADMINISTRATIVO/COORDENADOR):
         *   middleware('role:GESTOR')     → spatie role check
         *   middleware('permission:X')    → spatie permission check
         */
        $middleware->alias([
            'acesso'     => \App\Http\Middleware\CheckNivelAcesso::class,
            'owner'      => \App\Http\Middleware\CheckOwner::class,
            'gerente'    => \App\Http\Middleware\CheckGerente::class,
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

