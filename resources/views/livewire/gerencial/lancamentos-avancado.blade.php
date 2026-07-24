<div class="w-full">
    
    @push('head')
    <!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @endpush
    <x-page-header 
        backUrl="{{ route('dashboard.gerencial') }}" 
        icon="fas fa-filter" 
        iconColor="text-indigo-400" 
        title="Filtro Avançado" 
        subtitle="Pesquise, cruze dados e analise o histórico da operação">
    </x-page-header>
    
    {{-- ============================================================
         SEÇÃO: Filtros de Cruzamento Avançado
         ============================================================ --}}
    <div x-data="{ expanded: false }" class="mb-6 bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg overflow-hidden transition-all">
        <button @click="expanded = !expanded" class="w-full flex items-center justify-between px-6 py-4 bg-slate-900/50 hover:bg-slate-900/80 transition-colors border-b border-slate-700/50 focus:outline-none">
            <div class="flex items-center gap-3">
                <i class="fas fa-layer-group text-indigo-400 text-lg"></i>
                <h3 class="text-white font-bold tracking-wide">Filtros de Cruzamento Avançado</h3>
                <span class="text-xs text-slate-400 bg-slate-800 px-2 py-1 rounded-full border border-slate-700">Analise Múltiplos Cenários</span>
            </div>
            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" :class="{'rotate-180': expanded}"></i>
        </button>

        <div x-show="expanded" x-collapse style="display: none;">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    {{-- Filtro: Obra --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-map-marker-alt text-indigo-400 mr-1"></i> Por Obra/Cliente
                        </label>
                        <select wire:model.live="advancedObraId" class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Todas as Obras...</option>
                            @foreach($obrasOptions as $obra)
                                <option value="{{ $obra->id }}">{{ $obra->codigo }} - {{ $obra->nome }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">Filtre todos os registros alocados em uma obra específica.</p>
                    </div>

                    {{-- Filtro: Colaborador --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-user-hard-hat text-indigo-400 mr-1"></i> Por Colaborador
                        </label>
                        <select wire:model.live="advancedColaboradorId" class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Todos os Colaboradores...</option>
                            @foreach($colaboradoresOptions as $colab)
                                <option value="{{ $colab->id }}">{{ $colab->nome_completo }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">Analise onde este colaborador trabalhou.</p>
                    </div>

                    {{-- Filtro: Cargo --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-briefcase text-indigo-400 mr-1"></i> Por Cargo
                        </label>
                        <select wire:model.live="advancedCargoId" class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Todos os Cargos...</option>
                            @foreach($cargosOptions as $cargo)
                                <option value="{{ $cargo }}">{{ $cargo }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">Veja obras alocadas por um tipo de cargo.</p>
                    </div>

                    {{-- Filtro: Veículo --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-car text-indigo-400 mr-1"></i> Por Veículo
                        </label>
                        <select wire:model.live="advancedVeiculoId" class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">Todos os Veículos...</option>
                            @foreach($veiculosOptions as $veiculo)
                                <option value="{{ $veiculo->id }}">{{ $veiculo->descricao }} - {{ $veiculo->placa }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">Identifique quem utilizou este veículo e onde.</p>
                    </div>

                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-700/50 pt-4">
                    <button wire:click="limparFiltros" class="bg-slate-700 hover:bg-slate-600 text-white px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <i class="fas fa-eraser"></i> Limpar Tudo
                    </button>
                    <button wire:click="exportarRelatorio" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-900/20">
                        <i class="fas fa-file-excel"></i> Exportar Relatório
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SEÇÃO: Tabela Padrão (Visão Desktop)
         ============================================================ --}}
    <div class="hidden lg:block overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg relative">
        
        {{-- Loader Overlap --}}
        <div wire:loading class="absolute inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center">
            <div class="flex items-center gap-3 bg-slate-800 px-6 py-3 rounded-full shadow-2xl border border-indigo-500/30">
                <i class="fas fa-circle-notch fa-spin text-indigo-500 text-xl"></i>
                <span class="text-white font-bold tracking-wide">Atualizando dados...</span>
            </div>
        </div>

        <table class="min-w-full divide-y divide-slate-700/50">
            <thead>
                <tr class="bg-slate-900/50">
                    <th class="py-3 px-4 text-left">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Data</span>
                        <div class="flex flex-col gap-1">
                            <input type="date" wire:model.live="dataInicio" class="w-full bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1 text-slate-300 focus:border-indigo-500 outline-none" title="De">
                            <input type="date" wire:model.live="dataFim" class="w-full bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1 text-slate-300 focus:border-indigo-500 outline-none" title="Até">
                        </div>
                    </th>
                    <th class="py-3 px-4 text-left align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Obra / Cliente</span>
                        <input type="text" wire:model.live.debounce.500ms="filtroObra" placeholder="Filtrar Obra..." class="w-full bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1.5 text-slate-300 focus:border-indigo-500 outline-none placeholder-slate-600">
                    </th>
                    <th class="py-3 px-4 text-left align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Colaborador</span>
                        <input type="text" wire:model.live.debounce.500ms="filtroColaborador" placeholder="Filtrar Nome..." class="w-full bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1.5 text-slate-300 focus:border-indigo-500 outline-none placeholder-slate-600">
                    </th>
                    <th class="py-3 px-4 text-center align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Veículo</span>
                        <input type="text" wire:model.live.debounce.500ms="filtroVeiculo" placeholder="Placa/Mod..." class="w-full bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1.5 text-slate-300 text-center focus:border-indigo-500 outline-none placeholder-slate-600">
                    </th>
                    <th class="py-3 px-4 text-center align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Início</span>
                    </th>
                    <th class="py-3 px-4 text-center align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Fim</span>
                    </th>
                    <th class="py-3 px-4 text-center align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Total</span>
                    </th>
                    <th class="py-3 px-4 text-center align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Dia</span>
                    </th>
                    <th class="py-3 px-4 text-center align-top">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Status</span>
                        <input type="text" wire:model.live.debounce.500ms="filtroStatus" placeholder="Status..." class="w-full bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1.5 text-slate-300 text-center focus:border-indigo-500 outline-none placeholder-slate-600">
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($dados as $item)
                <tr class="hover:bg-slate-800/80 transition group">
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap font-mono">
                        {{ \Carbon\Carbon::parse($item->data_apontamento)->format('d/m/Y') }}
                    </td>
                    
                    <td class="py-3 px-4 text-sm text-slate-300">
                        @php
                            $localTipoDisplay = 'FORA DA OBRA';
                            if ($item->local_execucao === 'EXTERNO') {
                                $localTipoDisplay = 'DENTRO DA OBRA';
                                if ($item->projeto) {
                                    $cod      = $item->projeto->codigo ?? '';
                                    $localRef = $cod ? "{$cod} - {$item->projeto->nome}" : $item->projeto->nome;
                                } elseif ($item->codigoCliente) {
                                    $localRef = "{$item->codigoCliente->codigo} - {$item->codigoCliente->nome}";
                                } else {
                                    $localRef = 'Obra/Cliente não informado';
                                }
                            } else {
                                $localTipoDisplay = 'FORA DA OBRA';
                                if ($item->projeto) {
                                    $localRef = " {$item->projeto->codigo} - {$item->projeto->nome} ({$item->centroCusto?->nome})";
                                } elseif ($item->codigoCliente) {
                                    $localRef = " {$item->codigoCliente->codigo} - {$item->codigoCliente->nome} ({$item->centroCusto?->nome})";
                                } else {
                                    $localRef = " {$item->centroCusto?->nome}";
                                }
                            }
                        @endphp
                        <span class="text-white text-xs">{{ Str::limit($localRef, 50) }}</span>
                    </td>

                    <td class="py-3 px-4 text-sm">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-200 text-xs">
                                {{ $item->colaborador->nome_completo ?? 'N/A' }}
                            </span>
                            <span class="text-[10px] text-slate-500 uppercase tracking-wide">{{ $item->colaborador->cargo ?? '-' }}</span>
                        </div>
                    </td>

                    <td class="py-3 px-4 text-center">
                        @if($item->veiculo)
                            <div class="flex flex-col items-center">
                                <span class="text-emerald-400 font-mono text-[11px]">{{ $item->veiculo->placa }}</span>
                                <span class="text-slate-500 text-[9px] uppercase">{{ $item->veiculo->modelo }}</span>
                            </div>
                        @elseif($item->veiculo_manual_placa)
                            <div class="flex flex-col items-center">
                                <span class="text-emerald-400 font-mono text-[11px]">{{ $item->veiculo_manual_placa }}</span>
                                <span class="text-slate-500 text-[9px] uppercase">{{ $item->veiculo_manual_modelo }} (Ext)</span>
                            </div>
                        @else
                            <span class="text-slate-600 text-xs">-</span>
                        @endif
                    </td>

                    <td class="py-3 px-4 text-center">
                        <span class="text-green-500 font-medium font-mono text-sm">
                            {{ $item->hora_inicio ? substr($item->hora_inicio, 0, 5) : '-' }}
                        </span>
                    </td>

                    <td class="py-3 px-4 text-center">
                        <span class="{{ $item->hora_termino ? 'text-red-500' : 'text-yellow-500 animate-pulse' }} font-medium font-mono text-sm">
                            {{ $item->hora_termino ? substr($item->hora_termino, 0, 5) : 'Andamento' }}
                        </span>
                    </td>

                    <td class="py-3 px-4 text-center bg-slate-800/30">
                        <span class="text-white font-bold font-mono text-sm">{{ $item->duracao_total_str }}</span>
                    </td>
                    
                    <td class="py-3 px-4 text-center align-middle">
                        <span class="text-slate-600 text-xs select-none" title="Requer visão agrupada no histórico">-</span>
                    </td>

                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @php
                            $status = $item->status_aprovacao ?? 'EM_ANALISE';
                            $statusMap = [
                                'APROVADO'           => ['text' => 'APROVADO',           'class' => 'bg-green-500/10 text-green-400 border border-green-500/30 px-2.5 py-1 text-[10px] font-bold rounded-full'],
                                'REJEITADO'          => ['text' => 'REJEITADO',          'class' => 'bg-red-500/10 text-red-500 border border-red-500/30 px-2.5 py-1 text-[10px] font-bold rounded-full'],
                                'EM_ANALISE'         => ['text' => 'EM ANÁLISE',         'class' => 'bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 px-2.5 py-1 text-[10px] font-bold rounded-full'],
                                'SOLICITACAO_AJUSTE' => ['text' => '⚠ AJUSTE',           'class' => 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 px-2.5 py-1 text-[10px] font-bold rounded-full'],
                            ];
                            $s = $statusMap[$status] ?? $statusMap['EM_ANALISE'];
                        @endphp
                        <span class="{{ $s['class'] }}">{{ $s['text'] }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-16 text-center text-slate-500">
                        <i class="fas fa-search text-4xl mb-3 opacity-50"></i>
                        <p class="font-medium text-slate-300">Nenhum apontamento encontrado</p>
                        <p class="text-sm mt-1">Altere os filtros acima para buscar registros.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="p-4 border-t border-slate-700/50 bg-slate-900/30">
            {{ $dados->links() }}
        </div>
    </div>
</div>