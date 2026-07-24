<?php

namespace App\Services;

use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\Projeto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardService
{
    /**
     * Retorna os KPIs principais do mês para o Dashboard.
     */
    public function getKpis($mes, $ano, $filtros = [])
    {
        $inicioMes = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fimMes = Carbon::create($ano, $mes, 1)->endOfMonth();
        $hoje = Carbon::today();
        
        $queryBase = Apontamento::whereBetween('data_apontamento', [$inicioMes, $fimMes]);
        $queryBase = $this->aplicarFiltros($queryBase, $filtros);

        $totalApontamentos = (clone $queryBase)->count();
        
        $colaboradoresAtivos = (clone $queryBase)
            ->distinct('colaborador_id')
            ->count('colaborador_id');

        $diasUteis = 0;
        $periodo = \Carbon\CarbonPeriod::create($inicioMes, $hoje->min($fimMes));
        foreach ($periodo as $date) {
            if ($date->isWeekday()) $diasUteis++;
        }
        if ($diasUteis == 0) $diasUteis = 1;

        $divisor = ($colaboradoresAtivos * $diasUteis);
        $frequencia = $divisor > 0 ? ($totalApontamentos / $divisor) : 0;

        $enviaramHoje = Apontamento::whereDate('data_apontamento', $hoje)
            ->distinct('colaborador_id')
            ->count('colaborador_id');

        $dataLimite30 = Carbon::now()->subDays(30)->format('Y-m-d');
        $obrasAtivas = Apontamento::where('data_apontamento', '>=', $dataLimite30)
            ->whereNotNull('projeto_id')
            ->distinct('projeto_id')
            ->count('projeto_id');

        return [
            'total_apontamentos' => $totalApontamentos,
            'frequencia_pessoa_dia' => $frequencia,
            'colaboradores_ativos' => $colaboradoresAtivos,
            'enviaram_hoje' => $enviaramHoje,
            'obras_ativas' => $obrasAtivas
        ];
    }

    /**
     * Gráfico de Evolução (Linha) dos últimos 30 dias.
     */
    public function getDadosEvolucaoDiaria($filtros = [])
    {
        $dataLimite30 = Carbon::now()->subDays(30)->format('Y-m-d');
        $query = Apontamento::where('data_apontamento', '>=', $dataLimite30);
        $query = $this->aplicarFiltros($query, $filtros);

        return $query->select('data_apontamento as data', DB::raw('count(*) as total'))
            ->groupBy('data_apontamento')
            ->orderBy('data_apontamento', 'asc')
            ->get()
            ->map(function($item) {
                return [
                    'data' => Carbon::parse($item->data)->format('Y-m-d'),
                    'total' => $item->total
                ];
            })->toArray();
    }

    /**
     * Gráfico de Rosca (Donut) - Obras Ativas.
     */
    public function getDadosTopObras($filtros = [])
    {
        $dataLimite30 = Carbon::now()->subDays(30)->format('Y-m-d');
        $query = Apontamento::with('projeto')
            ->where('data_apontamento', '>=', $dataLimite30)
            ->whereNotNull('projeto_id');
        
        $query = $this->aplicarFiltros($query, $filtros);

        return $query->select('projeto_id', DB::raw('count(*) as total'))
            ->groupBy('projeto_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'codigo_obra' => $item->projeto ? $item->projeto->nome : 'N/A',
                    'total' => $item->total
                ];
            })->toArray();
    }

    /**
     * Lista para o menu lateral de filtros.
     */
    public function gerarListaLateral($tipoFiltro, $termoBusca = '')
    {
        $dataLimite30 = Carbon::now()->subDays(30)->format('Y-m-d');
        $lista = collect([]);

        switch ($tipoFiltro) {
            case 'obra':
                $lista = Projeto::whereHas('apontamentos', function($q) use ($dataLimite30) {
                        $q->where('data_apontamento', '>=', $dataLimite30);
                    })
                    ->select('id', 'nome as desc', 'codigo as id_display')
                    ->get()
                    ->map(fn($p) => (object)['id' => $p->id, 'desc' => $p->desc, 'display' => $p->id_display]);
                break;
            case 'colaborador':
                $lista = Colaborador::whereHas('apontamentos', function($q) use ($dataLimite30) {
                        $q->where('data_apontamento', '>=', $dataLimite30);
                    })
                    ->select('id', 'nome_completo as desc', 'cargo')
                    ->get()
                    ->map(fn($c) => (object)['id' => $c->id, 'desc' => $c->desc, 'display' => $c->cargo]);
                break;
            case 'veiculo':
                // Assumindo que Veiculo tem 'placa' e 'modelo'
                $lista = \App\Models\Veiculo::whereHas('apontamentos', function($q) use ($dataLimite30) {
                        $q->where('data_apontamento', '>=', $dataLimite30);
                    })
                    ->select('id', 'placa as display', 'modelo as desc')
                    ->get()
                    ->map(fn($v) => (object)['id' => $v->id, 'desc' => $v->desc, 'display' => $v->display]);
                break;
        }

        if ($termoBusca) {
            $busca = Str::lower($termoBusca);
            return $lista->filter(function($item) use ($busca) {
                return Str::contains(Str::lower($item->id), $busca) 
                    || Str::contains(Str::lower($item->desc), $busca)
                    || Str::contains(Str::lower($item->display ?? ''), $busca);
            })->values();
        }

        return $lista;
    }

    /**
     * Dados para o Calendário (Heatmap)
     */
    public function getDadosCalendario($mes, $ano, $filtros = [])
    {
        $inicioMes = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fimMes = Carbon::create($ano, $mes, 1)->endOfMonth();

        $query = Apontamento::whereBetween('data_apontamento', [$inicioMes, $fimMes]);
        $query = $this->aplicarFiltros($query, $filtros);

        return $query->select('data_apontamento', DB::raw('count(distinct colaborador_id) as qtd'))
            ->groupBy('data_apontamento')
            ->pluck('qtd', 'data_apontamento')
            ->toArray();
    }

    /**
     * Lançamentos Recentes para a Tabela
     */
    public function getLancamentosRecentes($expandido, $filtros = [])
    {
        $query = Apontamento::with(['colaborador', 'projeto', 'veiculo', 'centroCusto']);
        $query = $this->aplicarFiltros($query, $filtros);

        if ($expandido) {
            $dataInicio = Carbon::now()->subMonth()->startOfMonth();
            $query->where('data_apontamento', '>=', $dataInicio);
        } else {
            $dataInicio = Carbon::yesterday();
            $query->where('data_apontamento', '>=', $dataInicio);
        }

        return $query->orderBy('data_apontamento', 'desc')
            ->orderBy('hora_inicio', 'desc')
            ->limit(100) // limite de segurança para a view
            ->get();
    }

    /**
     * Filtros base
     */
    private function aplicarFiltros($query, $filtros)
    {
        if (!empty($filtros['tipo']) && !empty($filtros['valor'])) {
            switch ($filtros['tipo']) {
                case 'obra':
                    $query->where('projeto_id', $filtros['valor']);
                    break;
                case 'colaborador':
                    $query->where('colaborador_id', $filtros['valor']);
                    break;
                case 'veiculo':
                    $query->where('veiculo_id', $filtros['valor']);
                    break;
            }
        }
        return $query;
    }
}
