<?php

namespace App\Livewire\Gerencial;

use Livewire\Component;
use App\Services\DashboardService;
use Carbon\Carbon;

class Dashboard extends Component
{
    // --- ESTADO DA API & SINCRONIZAÇÃO ---
    public $erroApi = false;
    public $carregando = false;
    public $sincronizando = false;
    public $msgFeedback = '';

    // --- FILTROS ---
    public $tipoFiltro = 'obra'; 
    public $filtroValor = null;
    public $nomeFiltroSelecionado = '';
    public $termoBusca = '';
    public $mostrarBusca = false;

    // --- VARIÁVEIS VISUAIS ---
    public $kpis = [];
    public $graficos = [];
    public $listaLateral = [];
    public $tituloLista = 'Códigos de Obras';
    public $lancamentos = [];
    public $dadosCalendario = [];
    public $alertasTrabalhistas = [];
    
    // --- CALENDÁRIO ---
    public $mesAtual;
    public $anoAtual;
    public $modalAberto = false;
    public $dataSelecionada = null;
    public $detalhesEnviaram = [];
    public $detalhesPendentes = [];

    public $expandirTabela = false; 

    public function mount()
    {
        $this->mesAtual = Carbon::now()->month;
        $this->anoAtual = Carbon::now()->year;
        $this->atualizarDados();
    }

    public function sincronizarDados()
    {
        // No momento a integração real via API será adaptada para rodar
        // o Sólides ou outro método de sincronização.
        $this->sincronizando = true;
        $this->msgFeedback = '';
        $this->erroApi = false;

        try {
            // Simulando sincronização
            sleep(1);
            $this->atualizarDados();
            $this->msgFeedback = "Sincronizado! Registros atualizados.";
            $this->dispatch('notify', message: "Sincronização concluída com sucesso.");
        } catch (\Exception $e) {
            $this->erroApi = true;
            $this->msgFeedback = "Erro ao conectar: " . $e->getMessage();
            $this->dispatch('notify', message: "Erro na sincronização.", type: 'error');
        }

        $this->sincronizando = false;
    }

    public function mudarTipoFiltro($novoTipo)
    {
        if ($this->tipoFiltro !== $novoTipo) {
            $this->tipoFiltro = $novoTipo;
            $this->filtroValor = null;
            $this->nomeFiltroSelecionado = '';
            $this->termoBusca = '';
            
            $titulos = [
                'obra' => 'Códigos de Obras',
                'colaborador' => 'Colaboradores',
                'cargo' => 'Cargos',
                'veiculo' => 'Veículos',
            ];
            $this->tituloLista = $titulos[$novoTipo] ?? 'Lista';

            $this->atualizarDados();
        }
    }

    public function filtrarPorItem($valor)
    {
        $this->filtroValor = ($this->filtroValor === $valor) ? null : $valor;
        $this->atualizarDados();
    }

    public function filtrarPeloGrafico($tipo, $valor)
    {
        $this->tipoFiltro = $tipo;
        $this->filtroValor = ($this->filtroValor === $valor) ? null : $valor;
        $this->atualizarDados();
    }

    public function selecionarDataGrafico($dataYmd)
    {
        try {
            $dia = Carbon::parse($dataYmd)->day;
            $this->selecionarDia($dia); 
        } catch (\Exception $e) {
            // Ignora
        }
    }

    public function toggleBusca()
    {
        $this->mostrarBusca = !$this->mostrarBusca;
        if (!$this->mostrarBusca) $this->termoBusca = '';
    }

    public function mudarMes($direcao)
    {
        $data = Carbon::createFromDate($this->anoAtual, $this->mesAtual, 1)->addMonths($direcao);
        $this->mesAtual = $data->month;
        $this->anoAtual = $data->year;
        $this->atualizarDados();
    }

    public function toggleExpansao()
    {
        $this->expandirTabela = !$this->expandirTabela;
        $this->atualizarDados();
    }

    public function atualizarDados()
    {
        $this->carregando = true;
        
        $service = new DashboardService();
        $filtros = [
            'tipo' => $this->tipoFiltro,
            'valor' => $this->filtroValor
        ];

        $this->kpis = $service->getKpis($this->mesAtual, $this->anoAtual, $filtros);
        $this->listaLateral = $service->gerarListaLateral($this->tipoFiltro, $this->termoBusca);
        
        $topObrasData = $service->getDadosTopObras($filtros);
        $evolucaoData = $service->getDadosEvolucaoDiaria($filtros);

        $this->dispatch('update-charts', obras: $topObrasData, evolucao: $evolucaoData);

        $this->lancamentos = $service->getLancamentosRecentes($this->expandirTabela, $filtros);
        $this->dadosCalendario = $service->getDadosCalendario($this->mesAtual, $this->anoAtual, $filtros);

        // Buscar Alertas Trabalhistas Pendentes do mês
        $queryAlertas = \App\Models\Apontamento::with(['colaborador', 'projeto.cliente', 'centroCusto', 'veiculo'])
            ->where('flag_atencao', true)
            ->whereMonth('data_apontamento', $this->mesAtual)
            ->whereYear('data_apontamento', $this->anoAtual)
            ->orderBy('data_apontamento', 'desc');
            
        if (!empty($filtros['tipo']) && !empty($filtros['valor'])) {
            if ($filtros['tipo'] == 'obra') $queryAlertas->where('projeto_id', $filtros['valor']);
            if ($filtros['tipo'] == 'colaborador') $queryAlertas->where('colaborador_id', $filtros['valor']);
            if ($filtros['tipo'] == 'veiculo') $queryAlertas->where('veiculo_id', $filtros['valor']);
            if ($filtros['tipo'] == 'cargo') $queryAlertas->whereHas('colaborador', fn($q) => $q->where('cargo', $filtros['valor']));
        }

        $this->alertasTrabalhistas = $queryAlertas->get();

        // Identifica o nome do item selecionado para exibir no topo do gráfico
        if ($this->filtroValor) {
            if ($this->tipoFiltro == 'colaborador') {
                $c = \App\Models\Colaborador::find($this->filtroValor);
                $this->nomeFiltroSelecionado = $c ? $c->nome_completo . ' - ' . $c->cargo : $this->filtroValor;
            } elseif ($this->tipoFiltro == 'obra') {
                $p = \App\Models\Projeto::find($this->filtroValor);
                $this->nomeFiltroSelecionado = $p ? $p->nome : $this->filtroValor;
            } elseif ($this->tipoFiltro == 'veiculo') {
                $v = \App\Models\Veiculo::find($this->filtroValor);
                $this->nomeFiltroSelecionado = $v ? $v->placa . ' - ' . $v->descricao : $this->filtroValor;
            } else {
                $this->nomeFiltroSelecionado = $this->filtroValor;
            }
        } else {
            $this->nomeFiltroSelecionado = '';
        }

        $this->carregando = false;
    }

    public function selecionarDia($dia)
    {
        $data = Carbon::createFromDate($this->anoAtual, $this->mesAtual, $dia)->format('Y-m-d');
        $this->dataSelecionada = Carbon::parse($data)->format('d/m/Y');
        
        $filtros = [
            'tipo' => $this->tipoFiltro,
            'valor' => $this->filtroValor
        ];

        $query = \App\Models\Apontamento::with(['colaborador', 'projeto.cliente', 'centroCusto', 'veiculo'])
            ->whereDate('data_apontamento', $data);
            
        if (!empty($filtros['tipo']) && !empty($filtros['valor'])) {
            if ($filtros['tipo'] == 'obra') $query->where('projeto_id', $filtros['valor']);
            if ($filtros['tipo'] == 'colaborador') $query->where('colaborador_id', $filtros['valor']);
            if ($filtros['tipo'] == 'veiculo') $query->where('veiculo_id', $filtros['valor']);
            if ($filtros['tipo'] == 'cargo') $query->whereHas('colaborador', fn($q) => $q->where('cargo', $filtros['valor']));
        }

        $registrosDia = $query->get();

        $listaProcessada = $registrosDia->groupBy('colaborador_id')->map(function ($atividades, $colabId) {
            $sorted = $atividades->sortBy('hora_inicio');
            
            $primeiraAtividade = $sorted->first();
            $ultimaAtividade = $sorted->last();
            
            $totalSegundos = $atividades->sum(fn($a) => $a->duracao_em_segundos);

            return [
                'colaborador' => $primeiraAtividade->colaborador ? $primeiraAtividade->colaborador->nome_completo : 'Desconhecido',
                'cargo' => $primeiraAtividade->colaborador ? $primeiraAtividade->colaborador->cargo : '',
                'hora_inicio_visual' => $primeiraAtividade->hora_inicio,
                'hora_fim_visual' => $ultimaAtividade->hora_termino ?? 'Em andamento',
                'total_segundos' => $totalSegundos
            ];
        })->values()->toArray();
        
        $this->detalhesEnviaram = $listaProcessada;
        
        // Pega nomes (IDs) que enviaram hoje
        $enviaramIds = $registrosDia->pluck('colaborador_id')->unique();
        
        // CORREÇÃO N+1 e PROBLEMA DE MEMÓRIA: 
        // Em vez de carregar TODOS os apontamentos do mês e agrupar em PHP, 
        // buscamos apenas os nomes dos colaboradores ativos no período na base.
        $todosNomesMes = \App\Models\Colaborador::whereHas('apontamentos', function ($q) {
            $q->whereMonth('data_apontamento', $this->mesAtual)
              ->whereYear('data_apontamento', $this->anoAtual);
        })->pluck('nome_completo');
            
        $enviaramNomes = $registrosDia->map(fn($a) => $a->colaborador ? $a->colaborador->nome_completo : '')->unique();
        
        $this->detalhesPendentes = $todosNomesMes->diff($enviaramNomes)->values()->all();
        
        $this->modalAberto = true;
    }

    public function fecharModal() { 
        $this->modalAberto = false; 
    }
    
    public function render()
    {
        if ($this->lancamentos instanceof \Illuminate\Support\Collection) {
            $this->lancamentos->loadMissing(['colaborador', 'projeto.cliente', 'centroCusto', 'veiculo', 'codigoCliente']);
        }
        
        if ($this->alertasTrabalhistas instanceof \Illuminate\Support\Collection) {
            $this->alertasTrabalhistas->loadMissing(['colaborador', 'projeto.cliente', 'centroCusto', 'veiculo', 'codigoCliente']);
        }

        $startOfMonth = Carbon::createFromDate($this->anoAtual, $this->mesAtual, 1)->startOfMonth();
        
        return view('livewire.gerencial.dashboard', [
            'nomeMes' => ucfirst($startOfMonth->locale('pt_BR')->monthName),
            'diasVaziosInicio' => $startOfMonth->dayOfWeek,
            'totalDiasNoMes' => $startOfMonth->daysInMonth,
            'totalPessoas' => $this->kpis['colaboradores_ativos'] ?? 1
        ])->extends('layouts.app')->section('content');
    }
}
