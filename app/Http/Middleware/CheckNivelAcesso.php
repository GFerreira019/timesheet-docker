<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckNivelAcesso
{
    public function handle(Request $request, Closure $next, ...$niveis)
    {
        $user = $request->user();
        $nivel = ($user && $user->colaborador) ? strtoupper($user->colaborador->nivel_acesso) : 'OPERACIONAL';

        // ADMIN tem sempre passe livre para qualquer rota bloqueada por este middleware
        if ($nivel === 'ADMIN') {
            return $next($request);
        }

        // Verifica se o nível do utilizador está na lista de níveis permitidos
        if (in_array($nivel, array_map('strtoupper', $niveis))) {
            return $next($request);
        }

        abort(403, 'Acesso restrito a Gestores e Administradores.');
    }
}
