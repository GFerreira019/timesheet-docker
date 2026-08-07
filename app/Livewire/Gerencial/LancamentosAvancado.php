<?php

namespace App\Livewire\Gerencial;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\Projeto;
use App\Models\Veiculo;
use Carbon\Carbon;

class LancamentosAvancado extends Component
{
    use WithPagination;

    // --- Filtros de Coluna (Tabela) ---
    public $dataInicio;
    public $dataFim;
    
    public $filtroObra = '';
    public $filtroColaborador = '';
    public $filtroVeiculo = '';
    public $filtroStatus = '';

    // --- Filtros Avançados (Cruzamentos) ---
    public $advancedObraId = '';
    public $advancedColaboradorId = '';
    public $advancedCargoId = '';
    public $advancedVeiculoId = '';

    public function mount()
    {
        $this->dataInicio = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dataFim = Carbon::now()->format('Y-m-d');
    }

    public function updating($propertyName)
    {
        // Reseta a paginação sempre que um filtro for alterado
        $this->resetPage();
    }

    public function limparFiltros()
    {
        $this->reset([
            'filtroObra', 'filtroColaborador', 'filtroVeiculo', 'filtroStatus',
            'advancedObraId', 'advancedColaboradorId', 'advancedCargoId', 'advancedVeiculoId'
        ]);
        $this->dataInicio = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dataFim = Carbon::now()->format('Y-m-d');
    }

    public function getBaseQuery()
    {
        $query = Apontamento::with([
            'colaborador', 
            'projeto', 
            'veiculo',
            'codigoCliente',
            'centroCusto',
            'registradoPor',
            'auxiliar',
            'auxiliaresExtras'
        ])
            ->when($this->dataInicio, function($q) {
                $q->whereDate('data_apontamento', '>=', $this->dataInicio);
            })
            ->when($this->dataFim, function($q) {
                $q->whereDate('data_apontamento', '<=', $this->dataFim);
            });

        // --- Filtros de Coluna (Busca Textual) ---
        if (!empty($this->filtroObra)) {
            $query->whereHas('projeto', function($q) {
                $q->where('nome', 'like', '%' . $this->filtroObra . '%')
                  ->orWhere('codigo', 'like', '%' . $this->filtroObra . '%');
            });
        }

        if (!empty($this->filtroColaborador)) {
            $query->whereHas('colaborador', function($q) {
                $q->where('nome_completo', 'like', '%' . $this->filtroColaborador . '%');
            });
        }

        if (!empty($this->filtroVeiculo)) {
            $query->whereHas('veiculo', function($q) {
                $q->where('placa', 'like', '%' . $this->filtroVeiculo . '%')
                  ->orWhere('descricao', 'like', '%' . $this->filtroVeiculo . '%');
            });
        }

        if (!empty($this->filtroStatus)) {
            $query->where('status_aprovacao', 'like', '%' . $this->filtroStatus . '%');
        }

        // --- Filtros Avançados (Relacionamentos Exatos) ---
        if (!empty($this->advancedObraId)) {
            $query->where('projeto_id', $this->advancedObraId);
        }

        if (!empty($this->advancedColaboradorId)) {
            $query->where('colaborador_id', $this->advancedColaboradorId);
        }

        if (!empty($this->advancedCargoId)) {
            $query->whereHas('colaborador', function($q) {
                $q->where('cargo', $this->advancedCargoId);
            });
        }

        if (!empty($this->advancedVeiculoId)) {
            $query->where('veiculo_id', $this->advancedVeiculoId);
        }

        return $query;
    }

    public function render()
    {
        $query = $this->getBaseQuery();

        $dados = $query->orderBy('data_apontamento', 'desc')
                       ->orderBy('hora_inicio', 'desc')
                       ->paginate(50);

        // Opções para os Selects Avançados
        $obrasOptions = Projeto::orderBy('nome')->get();
        $colaboradoresOptions = Colaborador::orderBy('nome_completo')->get();
        $cargosOptions = Colaborador::select('cargo')->distinct()->whereNotNull('cargo')->pluck('cargo');
        $veiculosOptions = Veiculo::orderBy('descricao')->get();

        return view('livewire.gerencial.lancamentos-avancado', [
            'dados' => $dados,
            'obrasOptions' => $obrasOptions,
            'colaboradoresOptions' => $colaboradoresOptions,
            'cargosOptions' => $cargosOptions,
            'veiculosOptions' => $veiculosOptions,
        ])->extends('layouts.app')->section('content');
    }

    public function exportarRelatorio()
    {
        $query = $this->getBaseQuery()->orderBy('data_apontamento', 'desc')->orderBy('hora_inicio', 'desc');
        
        $fileName = 'Lancamentos_Avancados_' . now()->format('Ymd_His') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LancamentosAvancadosExport($query), 
            $fileName
        );
    }
}
