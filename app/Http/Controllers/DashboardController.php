<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Helpers\AcessoHelper;

class DashboardController extends Controller
{
    /**
     * Painel principal do sistema.
     * Acessível a todos os utilizadores autenticados.
     * Os cards são renderizados condicionalmente via AcessoHelper no Blade.
     *
     * GET /painel
     */
    public function index(): View
    {
        return view('painel', [
            'titulo'     => 'Painel de Módulos',
            'is_owner'   => AcessoHelper::isOwner(),
            'is_gerente' => AcessoHelper::isGerente(),
        ]);
    }
}
