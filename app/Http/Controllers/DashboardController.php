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

    /**
     * Tela de Dashboard Principal (Visão Geral)
     * GET /dashboard
     */
    public function dashboard(): View
    {
        $mesAtual = now()->month;
        $anoAtual = now()->year;

        // Recupera colaboradores ativos com apontamentos do mês
        $colaboradores = \App\Models\Colaborador::ativos()
            ->with(['apontamentos' => function ($q) use ($mesAtual, $anoAtual) {
                $q->whereMonth('data_apontamento', $mesAtual)
                  ->whereYear('data_apontamento', $anoAtual)
                  ->whereNotNull('hora_termino');
            }])
            ->get();

        // Calcula o total de horas
        $rankingColaboradores = $colaboradores->map(function ($colab) {
            $minutos = 0;
            foreach ($colab->apontamentos as $ap) {
                $inicio = \Carbon\Carbon::parse($ap->hora_inicio);
                $termino = \Carbon\Carbon::parse($ap->hora_termino);
                $minutos += $inicio->diffInMinutes($termino);
            }
            
            $colab->total_horas = round($minutos / 60, 1);
            return $colab;
        })
        ->filter(function ($colab) {
            return $colab->total_horas > 0;
        })
        ->sortByDesc('total_horas')
        ->values()
        ->take(5);

        $totalProjetos = \App\Models\Projeto::ativos()->count();
        $totalColaboradores = \App\Models\Colaborador::ativos()->count();
        $alertasGestao = \App\Models\Apontamento::whereMonth('data_apontamento', $mesAtual)
            ->whereYear('data_apontamento', $anoAtual)
            ->whereIn('status_aprovacao', ['EM_ANALISE', 'SOLICITACAO_AJUSTE', 'DIVERGENTE'])
            ->count();

        return view('dashboard', compact('rankingColaboradores', 'totalProjetos', 'totalColaboradores', 'alertasGestao'));
    }
}
