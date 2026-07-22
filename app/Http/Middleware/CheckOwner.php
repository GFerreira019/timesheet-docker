<?php

namespace App\Http\Middleware;

use App\Helpers\AcessoHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: CheckOwner
 *
 * Equivalente ao decorator Django:
 *   @user_passes_test(is_owner)
 *
 * Protege rotas que exigem nível Owner (superusuário).
 * Uso em routes/web.php:
 *   Route::middleware('owner')->group(function () { ... });
 */
class CheckOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !AcessoHelper::isOwner()) {
            abort(403, 'Acesso restrito ao Owner do sistema.');
        }

        return $next($request);
    }
}
