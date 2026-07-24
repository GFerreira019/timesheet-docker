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
    public $termoBusca = '';
    public $mostrarBusca = false;

    // --- VARIÁVEIS VISUAIS ---
    public $kpis = [];
    public $graficos = [];
    public $listaLateral = [];
    public $tituloLista = 'Códigos de Obras';
    public $lancamentos = [];
    public $dadosCalendario = [];
    
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
            $this->termoBusca = '';
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

        $query = \App\Models\Apontamento::with('colaborador')
            ->whereDate('data_apontamento', $data);
            
        if (!empty($filtros['tipo']) && !empty($filtros['valor'])) {
            if ($filtros['tipo'] == 'obra') $query->where('projeto_id', $filtros['valor']);
            if ($filtros['tipo'] == 'colaborador') $query->where('colaborador_id', $filtros['valor']);
            if ($filtros['tipo'] == 'veiculo') $query->where('veiculo_id', $filtros['valor']);
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
        
        // Pendentes: todos que enviaram no mes - os que enviaram hoje (simplificando)
        $todosNomesMes = \App\Models\Apontamento::with('colaborador')
            ->whereMonth('data_apontamento', $this->mesAtual)
            ->distinct('colaborador_id')
            ->get()
            ->map(fn($a) => $a->colaborador ? $a->colaborador->nome_completo : '')
            ->filter();
            
        $enviaramNomes = $registrosDia->map(fn($a) => $a->colaborador ? $a->colaborador->nome_completo : '')->unique();
        
        $this->detalhesPendentes = $todosNomesMes->diff($enviaramNomes)->values()->all();
        
        $this->modalAberto = true;
    }

    public function fecharModal() { 
        $this->modalAberto = false; 
    }
    
    public function render()
    {
        $startOfMonth = Carbon::createFromDate($this->anoAtual, $this->mesAtual, 1)->startOfMonth();
        
        return view('livewire.gerencial.dashboard', [
            'nomeMes' => ucfirst($startOfMonth->locale('pt_BR')->monthName),
            'diasVaziosInicio' => $startOfMonth->dayOfWeek,
            'totalDiasNoMes' => $startOfMonth->daysInMonth,
            'totalPessoas' => $this->kpis['colaboradores_ativos'] ?? 1
        ])->extends('layouts.app')->section('content');
    }
}
