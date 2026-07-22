<?php

namespace App\Http\Middleware;

use App\Helpers\AcessoHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: CheckGerente
 *
 * Equivalente ao decorator Django:
 *   @user_passes_test(is_gerente)
 *
 * Protege rotas do painel de aprovações e análise.
 * Acesso: Owner OU usuário com role GESTOR.
 *
 * Uso em routes/web.php:
 *   Route::middleware('gerente')->group(function () { ... });
 */
class CheckGerente
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !AcessoHelper::isGerente()) {
            abort(403, 'Acesso restrito a Gestores e Administradores.');
        }

        return $next($request);
    }
}
