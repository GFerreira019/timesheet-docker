<div
    wire:poll.60s="atualizarDados" 
    x-data="{ 
    modalOpen: false, 
    modalTitle: '', 
    modalContent: '',
    openModal(title, content)
    {
        this.modalTitle = title;
        this.modalContent = content;
        this.modalOpen = true;
    }
}">
    @push('head')
        {{-- FontAwesome 6 — conforme Seção 6.1 do Design System --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @endpush

    <x-page-header 
        backUrl="{{ route('painel') }}" 
        icon="fas fa-chart-line" 
        iconColor="text-indigo-400" 
        title="Dashboard Gerencial" 
        subtitle="Visão geral da produção e utilização dos colaboradores">
    </x-page-header>

    <div class="flex justify-end gap-2 mb-4">
        <a href="{{ route('lancamentos.avancado') }}" class="flex items-center gap-2 text-sm font-bold px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Filtros Avançados
        </a>
    </div>
        

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden group hover:border-indigo-500/30 transition-all">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500 transition-all group-hover:w-2"></div>
            <p class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-4 flex justify-between">
                <span>Apontamentos</span><span class="text-gray-500 lowercase font-normal italic">mês atual</span>
            </p>
            <div class="flex items-end justify-between">
                <div>
                    <h3 class="text-4xl font-bold text-white tracking-tight">{{ number_format($kpis['total_apontamentos'] ?? 0, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-500 uppercase mt-1">Registros recebidos</p>
                </div>
                <div class="text-right border-l border-slate-800 pl-4">
                    <span class="text-xl font-bold text-indigo-300">{{ number_format($kpis['frequencia_pessoa_dia'] ?? 0, 2, ',', '.') }}</span>
                    <p class="text-[10px] text-gray-500 uppercase">Média (Pessoa/Dia)</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden group hover:border-blue-500/30 transition-all">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500 transition-all group-hover:w-2"></div>
            <p class="text-xs text-blue-400 font-bold uppercase tracking-wider mb-4">Colaboradores</p>
            <div class="flex items-end justify-between">
                <div>
                    <h3 class="text-4xl font-bold text-white tracking-tight">{{ $kpis['colaboradores_ativos'] ?? 0 }}</h3>
                    <p class="text-[10px] text-gray-500 uppercase mt-1">Ativos no mês</p>
                </div>
                <div class="text-right border-l border-slate-800 pl-4">
                    <div class="flex items-center gap-2 justify-end">
                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                        <span class="text-xl font-bold text-emerald-400">{{ $kpis['enviaram_hoje'] ?? 0 }}</span>
                    </div>
                    <p class="text-[10px] text-gray-500 uppercase">Enviaram Hoje</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden group hover:border-orange-500/30 transition-all">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-500 transition-all group-hover:w-2"></div>
            <p class="text-xs text-orange-400 font-bold uppercase tracking-wider mb-4">Projetos/Obras</p>
            <div class="flex items-end justify-between">
                <div>
                    <h3 class="text-4xl font-bold text-white tracking-tight">{{ $kpis['obras_ativas'] ?? 0 }}</h3>
                    <p class="text-[10px] text-gray-500 uppercase mt-1">Ativas (30 dias)</p>
                </div>
                <div class="p-2 bg-orange-500/10 rounded-lg text-orange-500">
                    <svg class="h-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden mb-8">
        <div class="absolute top-0 left-0 w-1.5 h-full bg-purple-500"></div>
        <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
            <h3 class="text-purple-400 font-bold text-lg uppercase tracking-wider flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Calendário de Entregas
            </h3>
            <div class="flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700">
                <button wire:click="mudarMes(-1)" class="p-1.5 text-gray-400 hover:text-white hover:bg-slate-700 rounded transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>
                <span class="px-4 text-sm font-bold text-gray-200 min-w-[140px] text-center capitalize">{{ $nomeMes }} <span class="text-gray-500">/</span> {{ $anoAtual }}</span>
                <button wire:click="mudarMes(1)" class="p-1.5 text-gray-400 hover:text-white hover:bg-slate-700 rounded transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>
            </div>
        </div>
        <div class="grid grid-cols-7 gap-2 mb-2">
            @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $diaSemana)
                <div class="bg-slate-950 border-b border-slate-800 rounded-lg text-center text-xs font-bold text-gray-500 uppercase py-2">{{ $diaSemana }}</div>
            @endforeach
            @for($i = 0; $i < $diasVaziosInicio; $i++) <div class="bg-transparent h-28 md:h-32"></div> @endfor
            @for($dia = 1; $dia <= $totalDiasNoMes; $dia++)
                @php
                    $dataCurrent = sprintf('%04d-%02d-%02d', $anoAtual, $mesAtual, $dia);
                    $qtdEnvios = $dadosCalendario[$dataCurrent] ?? 0;
                    $bgClass = $qtdEnvios == 0 ? 'bg-slate-950/50 border-slate-800 text-gray-600' : 'bg-slate-800 border-emerald-500/30 text-white';
                    $statusColor = $qtdEnvios == 0 ? 'bg-gray-700' : ($qtdEnvios >= (($totalPessoas ?? 1) * 0.8) ? 'bg-emerald-500' : 'bg-yellow-500');
                    $isWeekend = \Carbon\Carbon::createFromDate($anoAtual, $mesAtual, $dia)->isWeekend();
                    if($isWeekend && $qtdEnvios == 0) $bgClass = 'bg-slate-900/30 border-slate-800/50 text-gray-700';
                @endphp
                <div wire:click="selecionarDia({{ $dia }})" class="{{ $bgClass }} h-28 md:h-32 border rounded-lg p-2 md:p-3 cursor-pointer transition-all flex flex-col justify-between hover:scale-[1.02] hover:shadow-lg hover:border-indigo-500/50 group relative">
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-lg {{ $isWeekend ? 'text-gray-600' : '' }}">{{ $dia }}</span>
                        @if($qtdEnvios > 0)<span class="text-[10px] text-indigo-300 bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20">Ver</span>@endif
                    </div>
                    @if($qtdEnvios > 0)
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-[10px] md:text-xs text-gray-400">
                                <span>Entregues</span><span class="text-white font-bold">{{ $qtdEnvios }}</span>
                            </div>
                            <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                <div class="{{ $statusColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ ($totalPessoas > 0 ? min(($qtdEnvios / $totalPessoas) * 100, 100) : 0) }}%"></div>
                            </div>
                        </div>
                    @else 
                        <span class="text-[10px] text-gray-700 self-center font-medium mt-auto mb-2 opacity-50">-</span> 
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-indigo-400 font-bold text-lg uppercase tracking-wider">Evolução Diária</h3>
                @if($filtroValor)
                    <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2 py-1 rounded border border-indigo-500/30 flex items-center gap-1 animate-fade-in">
                        <span class="opacity-50 uppercase text-[10px]">{{ $tipoFiltro }}:</span>
                        <span class="font-bold">{{ Str::limit($filtroValor, 15) }}</span>
                        <button wire:click="filtrarPorItem('{{ $filtroValor }}')" class="ml-1 hover:text-white bg-indigo-500/20 rounded-full w-4 h-4 flex items-center justify-center">&times;</button>
                    </span>
                @endif
            </div>
            <div wire:ignore id="chart-timeline" class="h-64 flex items-center justify-center text-gray-600">
                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Carregando gráfico...
            </div>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-500"></div>
            <h3 class="text-orange-400 font-bold mb-1 text-lg uppercase tracking-wider">Obras ativas</h3>
            <p class="text-xs text-gray-500 text-left mt-0 leading-tight">
                Últimos 30 dias
            </p>

            <div wire:ignore id="chart-donut" class="flex justify-center h-64"></div>
        </div>

    </div>

    <div class="relative bg-slate-900 grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-slate-800 rounded-xl border border-slate-800 p-4 shadow-lg flex flex-col gap-2 h-fit">
            <h4 class="text-gray-400 text-xs font-bold uppercase mb-2 px-2">Categorias de Filtro</h4>
            @foreach(['obra' => ['Códigos de Obras', 'pink', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'], 'colaborador' => ['Colaboradores', 'blue', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'], 'cargo' => ['Cargos', 'purple', 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'], 'veiculo' => ['Veículos', 'emerald', 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z']] as $key => $conf)
                <button wire:click="mudarTipoFiltro('{{ $key }}')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all {{ $tipoFiltro === $key ? 'bg-'.$conf[1].'-500 text-white shadow-lg shadow-'.$conf[1].'-500/20 translate-x-1' : 'text-gray-400 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ $tipoFiltro === $key ? 'text-white' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $conf[2] }}" /></svg> 
                    {{ $conf[0] }}
                </button>
            @endforeach
            
            <div class="mt-4 pt-4 border-t border-slate-800">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="termoBusca" placeholder="Buscar na lista..." class="w-full bg-slate-950 border border-slate-700 rounded-md py-2 pl-3 pr-8 text-sm text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-gray-600">
                    <svg class="w-4 h-4 text-gray-600 absolute right-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800 flex flex-col shadow-lg relative overflow-hidden h-[450px]">
            <div class="absolute top-0 left-0 w-1.5 h-full {{ $tipoFiltro == 'obra' ? 'bg-pink-500' : ($tipoFiltro == 'colaborador' ? 'bg-blue-500' : ($tipoFiltro == 'cargo' ? 'bg-purple-500' : 'bg-emerald-500')) }}"></div>
            <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-800 z-10">
                <div>
                    <h3 class="text-white font-bold text-lg uppercase tracking-wider">{{ $tituloLista }}</h3>
                    <p class="text-xs text-gray-500">Clique para filtrar o dashboard</p>
                </div>
                <span class="text-xs font-bold bg-slate-800 text-gray-400 px-2 py-1 rounded border border-slate-700">{{ count($listaLateral) }} itens</span>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse($listaLateral as $item)
                        <div wire:click="filtrarPorItem('{{ $item->id }}')" class="cursor-pointer p-3 rounded-lg border transition-all relative group {{ $filtroValor === $item->id ? 'bg-slate-800 border-white/20 ring-1 ring-white/30 shadow-lg' : 'bg-slate-800/40 border-slate-700 hover:bg-slate-800 hover:border-slate-600' }}">
                            <div class="flex justify-between items-start gap-3">
                                <div class="overflow-hidden">
                                    <span class="block text-sm font-bold truncate {{ $filtroValor === $item->id ? 'text-white' : 'text-gray-300 group-hover:text-white' }}">{{ Str::limit($item->id, 25) }}</span>
                                    @if(isset($item->desc) && $item->desc != $item->id)
                                        <span class="text-xs text-gray-500 block truncate mt-0.5 group-hover:text-gray-400">{{ Str::limit($item->desc, 30) }}</span>
                                    @endif
                                </div>
                                @if($filtroValor === $item->id)
                                    <div class="mt-1 h-2 w-2 rounded-full bg-white shadow-glow"></div>
                                @endif
                            </div>
                        </div>
                    @empty 
                        <div class="col-span-full py-12 text-center">
                            <svg class="w-12 h-12 text-gray-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-gray-500 text-sm">Nenhum registro encontrado para esta busca.</p>
                        </div> 
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h3 class="text-emerald-400 font-bold text-lg uppercase tracking-wider flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Últimos Lançamentos
                <span class="text-xs font-normal text-gray-500 ml-2 normal-case">
                    ({{ $expandirTabela ? 'Desde ' . \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('d/m') : 'Hoje e Ontem' }})
                </span>
            </h3>

            <div class="flex gap-2">
                <button wire:click="toggleExpansao" class="text-xs font-bold px-3 py-1.5 rounded border transition-colors {{ $expandirTabela ? 'bg-slate-800 text-white border-slate-600' : 'bg-slate-800/50 text-gray-400 border-slate-700 hover:text-white' }}">
                    {{ $expandirTabela ? 'Ver Menos' : 'Ver Mês Anterior' }}
                </button>
            </div>
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-700">
            <table class="min-w-full divide-y divide-slate-700">
                <thead>
                    <tr class="bg-slate-950 border-b border-slate-800">
                        <th class="py-3 px-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="py-3 px-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Obra / Justificativa</th>
                        <th class="py-3 px-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Colaborador</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Veículo</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Origem</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Início</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Fim</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Extras</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Info</th>
                        <th class="py-3 px-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900">
                    @forelse($lancamentos as $l)
                    <tr class="hover:bg-slate-800/80 transition duration-150 group">
                        <td class="py-3 px-3 text-sm text-gray-300 whitespace-nowrap font-mono">
                            {{ \Carbon\Carbon::parse($l->data_apontamento)->format('d/m/Y') }}
                        </td>
                        
                        <td class="py-3 px-3">
                            <div class="flex flex-col">
                                <span class="text-gray-300 font-medium text-xs">{{ $l->projeto->codigo ?? 'N/A' }}</span>
                                <span class="text-[11px] text-gray-500 uppercase">{{ Str::limit($l->projeto->nome ?? 'N/A', 30) }}</span>
                            </div>
                        </td>

                        <td class="py-3 px-3">
                            <div class="flex flex-col">
                                <span class="text-white font-bold text-xs">{{ Str::limit($l->colaborador->nome_completo ?? 'N/A', 25) }}</span>
                                <span class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $l->colaborador->cargo ?? 'Colaborador' }}</span>
                            </div>
                        </td>

                        <td class="py-3 px-2 text-center">
                            @if($l->veiculo)
                                <div class="flex justify-center" title="{{ $l->veiculo->modelo ?? '' }} - {{ $l->veiculo->placa ?? '' }}">
                                    <svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                                </div>
                            @else
                                <span class="text-gray-700 text-xs">-</span>
                            @endif
                        </td>

                        <td class="py-3 px-2 text-center">
                            <div class="flex justify-center" title="{{ $l->local_execucao }}">
                                {{-- 1. Em Campo / Dentro da Obra (EXTERNO) — ícone Casa Índigo --}}
                                @if($l->local_execucao == 'EXTERNO')
                                    <svg class="w-4 h-4 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                    </svg>
                                
                                {{-- 2. Legado INT_CLI (manter compatibilidade) --}}
                                @elseif($l->local_execucao == 'INT_CLI')
                                    <div class="flex justify-center" title="Cliente">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                            <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.435-.608-7.92-1.708z"></path>
                                        </svg>
                                    </div>
                                
                                {{-- 3. Na Base / Fora da Obra (INTERNO) — ícone Prédio Roxo --}}
                                @elseif($l->local_execucao == 'INTERNO')
                                    <svg class="w-4 h-4 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                    </svg>
                                
                                {{-- 4. Padrão (Globo Cinza) --}}
                                @else
                                    <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                    </svg>
                                @endif
                            </div>
                        </td>

                        <td class="py-3 px-2 text-xs text-emerald-400 font-mono font-bold text-center">
                            {{ substr($l->hora_inicio, 0, 5) }}
                        </td>

                        <td class="py-3 px-2 text-xs text-red-400 font-mono font-bold text-center">
                            {{ substr($l->hora_termino ?? '', 0, 5) }}
                        </td>

                        <td class="py-3 px-2 text-center">
                            {{-- 1. A div principal NÃO pode ter 'group' (apenas flex e gap) --}}
                            <div class="flex items-center justify-center gap-2">
                                
                                {{-- Ícone Dorme Fora --}}
                                @if($l->dorme_fora)
                                    {{-- MUDANÇA 1: Adicionei '/dorme' ao nome do group --}}
                                    <div class="group/dorme relative flex items-center justify-center">
                                        <svg class="w-4 h-4 text-indigo-400 cursor-help" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 2c-1.05 0-2.05.16-3 .46 2.89 1.94 4.8 5.16 4.8 8.83s-1.91 6.89-4.8 8.83c.95.3 1.95.46 3 .46 5.52 0 10-4.48 10-10S14.52 2 9 2z"/>
                                        </svg>
                                        
                                        {{-- MUDANÇA 2: O hover agora só responde ao '/dorme' --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 group-hover/dorme:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                                            <span class="bg-gray-800 text-white text-[10px] py-1 px-2 rounded shadow-lg whitespace-nowrap">
                                                Dorme fora
                                            </span>
                                            {{-- Setinha --}}
                                            <div class="w-2 h-2 bg-gray-800 rotate-45 absolute left-1/2 -translate-x-1/2 -bottom-1"></div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Ícone Em Plantão --}}
                                @if($l->em_plantao)
                                    {{-- MUDANÇA 3: Adicionei '/plantao' ao nome do group --}}
                                    <div class="group/plantao relative flex items-center justify-center">
                                        <svg class="w-4 h-4 text-pink-500 cursor-help" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                        </svg>

                                        {{-- MUDANÇA 4: O hover agora só responde ao '/plantao' --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 group-hover/plantao:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                                            <span class="bg-gray-800 text-white text-[10px] py-1 px-2 rounded shadow-lg whitespace-nowrap">
                                                Plantão
                                            </span>
                                            {{-- Setinha --}}
                                            <div class="w-2 h-2 bg-gray-800 rotate-45 absolute left-1/2 -translate-x-1/2 -bottom-1"></div>
                                        </div>
                                    </div>
                                @endif

                                @if(!$l->dorme_fora && !$l->em_plantao)
                                    <span class="text-gray-700 text-[10px]">-</span>
                                @endif
                            </div>
                        </td>

                        <td class="py-3 px-2 text-center">
                            @if(isset($l->ocorrencias) && !empty($l->ocorrencias))
                                <button @click="openModal('Observações', '{{ addslashes($l->ocorrencias) }}')" class="text-cyan-400 hover:text-white transition" title="Ver Observação">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                </button>
                            @else <span class="text-gray-700 opacity-30 cursor-default">-</span> @endif
                        </td>

                        <td class="py-3 px-2 text-center">
                            @if($l->status_aprovacao == 'PENDENTE' || $l->status_aprovacao == 'EM_ANALISE')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-500/10 text-yellow-500 border border-yellow-500/20">PEND</span>
                            @elseif($l->status_aprovacao == 'APROVADO')
                                <span class="text-emerald-500" title="Aprovado"><svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></span>
                            @else
                                <span class="text-gray-500 text-[10px]">{{ $l->status_aprovacao ?? '-' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty 
                    <tr><td colspan="10" class="text-center py-12 text-gray-500 italic">Nenhum lançamento encontrado recentemente.</td></tr> 
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endassets

    @script
    <script>
        let chartTimeline, chartDonut;

        // Função principal que desenha os gráficos
        const initCharts = (dataObras = [], dataEvolucao = []) => {
            
            // ==========================================
            // 1. GRÁFICO DE LINHA (Timeline / Evolução)
            // ==========================================
            const labelsLine = dataEvolucao.map(d => { 
                let dt = new Date(d.data); 
                // Formata dia/mês para exibir no eixo X (ex: 23/12)
                return dt.getUTCDate().toString().padStart(2, '0') + '/' + (dt.getUTCMonth()+1).toString().padStart(2, '0'); 
            });
            const seriesLine = dataEvolucao.map(d => parseInt(d.total));

            const optionsLine = {
                chart: { 
                    type: 'area', 
                    height: 250, 
                    toolbar: { show: false }, 
                    background: 'transparent', 
                    animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    // --- AQUI ESTÁ A MÁGICA DO CLIQUE ---
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            // Pega o índice do ponto clicado
                            let index = config.dataPointIndex;
                            // Busca a data original (Y-m-d) no array de dados
                            let item = dataEvolucao[index];
                            
                            if(item && item.data) {
                                // Chama o método do Livewire para abrir o modal do dia
                                $wire.selecionarDataGrafico(item.data);
                            }
                        }
                    }
                },
                series: [{ name: 'Registros', data: seriesLine }],
                xaxis: { 
                    categories: labelsLine, 
                    labels: { style: { colors: '#94a3b8', fontSize: '11px' } }, 
                    axisBorder: { show: false }, 
                    axisTicks: { show: false } 
                },
                yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
                grid: { borderColor: '#334155', strokeDashArray: 4, padding: { left: 10, right: 10 } },
                colors: ['#6366f1'], 
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1, stops: [0, 90, 100] } },
                stroke: { curve: 'smooth', width: 3 }, 
                theme: { mode: 'dark' },
                dataLabels: { enabled: false },
                tooltip: { theme: 'dark' }
            };

            if(chartTimeline) chartTimeline.destroy();
            chartTimeline = new ApexCharts(document.querySelector("#chart-timeline"), optionsLine);
            chartTimeline.render();


            // ==========================================
            // 2. GRÁFICO DE ROSCA (Obras Ativas)
            // ==========================================
            const labelsObras = dataObras.map(d => d.codigo_obra || 'N/A');
            const seriesObras = dataObras.map(d => parseInt(d.total || 0));

            const optionsDonut = {
                chart: { 
                    type: 'donut', 
                    height: 320, 
                    background: 'transparent', 
                    fontFamily: 'inherit',
                    // --- AQUI ESTÁ A MÁGICA DO CLIQUE ---
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                            // Pega o índice da fatia clicada
                            let index = config.dataPointIndex;
                            // Pega o nome da obra correspondente ao índice
                            let codigoObra = labelsObras[index];
                            
                            if(codigoObra) {
                                // Chama o Livewire para filtrar por 'obra' com esse código
                                $wire.filtrarPeloGrafico('obra', codigoObra);
                            }
                        }
                    }
                },
                series: seriesObras.length ? seriesObras : [1], 
                labels: labelsObras.length ? labelsObras : ['Sem dados'],
                colors: ['#f97316', '#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#ec4899'], 
                plotOptions: { 
                    pie: { 
                        donut: { 
                            size: '70%', 
                            labels: { 
                                show: true, 
                                total: { show: true, label: 'Total', color: '#fff', fontSize: '16px', fontWeight: 'bold' }, 
                                value: { color: '#cbd5e1', fontSize: '24px', fontWeight: 'bold' } 
                            } 
                        } 
                    } 
                },
                legend: { position: 'bottom', labels: { colors: '#94a3b8' }, itemMargin: { horizontal: 10, vertical: 5 } },
                stroke: { show: true, width: 2, colors: ['#0f172a'] }, 
                theme: { mode: 'dark' },
                tooltip: { theme: 'dark' }
            };

            if(chartDonut) chartDonut.destroy();
            chartDonut = new ApexCharts(document.querySelector("#chart-donut"), optionsDonut);
            chartDonut.render();
        };

        // Inicializa na primeira carga
        initCharts(
            @json($graficos['obras'] ?? []), // Fallback caso vazio na primeira carga
            @json($graficos['evolucao'] ?? [])
        );

        // Atualiza quando o Livewire envia novos dados
        $wire.on('update-charts', (data) => {
            const payload = data[0] || data; 
            const obras = payload.obras || [];
            const evolucao = payload.evolucao || [];
            initCharts(obras, evolucao);
        });
    </script>
    @endscript

    <div x-show="modalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-950/80 backdrop-blur-sm" @click="modalOpen = false"></div>
        <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-slate-900 border border-slate-700 rounded-xl shadow-2xl w-full max-w-lg p-6">
            <h3 class="text-white font-bold mb-4 text-xl border-b border-slate-800 pb-2" x-text="modalTitle"></h3>
            <div class="bg-slate-950/50 p-4 rounded-lg border border-slate-800/50">
                <p class="text-gray-300 text-sm whitespace-pre-line leading-relaxed" x-text="modalContent"></p>
            </div>
            <div class="mt-6 flex justify-end">
                <button @click="modalOpen = false" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition">Fechar</button>
            </div>
        </div>
    </div>

    @if($modalAberto)
        <div class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/90 backdrop-blur-md transition-opacity" wire:click="fecharModal"></div>
            
            <div class="relative bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col animate-fade-in-up">
                
                <div class="bg-slate-800/50 p-6 border-b border-slate-700 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-2xl font-bold text-white">Detalhes do Dia</h3>
                        <p class="text-indigo-400 font-medium">{{ $dataSelecionada }}</p>
                    </div>
                    <button wire:click="fecharModal" class="text-gray-400 hover:text-white p-2 hover:bg-slate-700 rounded-full transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 bg-slate-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-emerald-400 font-bold text-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Enviaram
                                </h4>
                                <span class="bg-emerald-500/10 text-emerald-400 text-xs font-bold px-2.5 py-0.5 rounded-full border border-emerald-500/20">{{ count($detalhesEnviaram) }}</span>
                            </div>
                            
                            <div class="bg-slate-950/50 rounded-xl border border-slate-800/50 h-96 overflow-y-auto custom-scrollbar">
                                @if(count($detalhesEnviaram) > 0)
                                    <ul class="divide-y divide-slate-800/50">
                                        @foreach($detalhesEnviaram as $env)
                                            @php
                                                $totalSec = $env['total_segundos'];
                                                $horas = floor($totalSec / 3600);
                                                $minutos = floor(($totalSec % 3600) / 60);
                                                $duracaoTxt = sprintf('%02dh %02dm', $horas, $minutos);
                                                
                                                $inicio = substr($env['hora_inicio_visual'] ?? '00:00', 0, 5);
                                                $fim = substr($env['hora_fim_visual'] ?? '00:00', 0, 5);
                                            @endphp
                                            <li class="p-3 hover:bg-emerald-500/5 transition flex justify-between items-center group">
                                                <div>
                                                    <p class="text-gray-200 text-sm font-medium">{{ $env['colaborador'] }}</p>
                                                    <p class="text-[10px] text-gray-500 uppercase">{{ $env['cargo'] ?? 'Colaborador' }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="block text-xs text-gray-400 font-mono mb-0.5" title="Primeiro Início - Último Fim">{{ $inicio }} - {{ $fim }}</span>
                                                    <span class="text-emerald-400 font-bold text-xs bg-emerald-500/10 px-2 py-0.5 rounded">{{ $duracaoTxt }}</span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="h-full flex flex-col items-center justify-center text-gray-500">
                                        <svg class="w-10 h-10 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                        <p class="text-sm">Ninguém enviou ainda.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-red-400 font-bold text-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Pendentes
                                </h4>
                                <span class="bg-red-500/10 text-red-400 text-xs font-bold px-2.5 py-0.5 rounded-full border border-red-500/20">{{ count($detalhesPendentes) }}</span>
                            </div>

                            <div class="bg-slate-950/50 rounded-xl border border-slate-800/50 h-96 overflow-y-auto custom-scrollbar">
                                @if(count($detalhesPendentes) > 0)
                                    <ul class="divide-y divide-slate-800/50">
                                        @foreach($detalhesPendentes as $pen)
                                            <li class="p-3 hover:bg-red-500/5 transition flex items-center group">
                                                <div class="w-2 h-2 rounded-full bg-red-500/50 mr-3 group-hover:bg-red-500"></div>
                                                <p class="text-gray-400 text-sm group-hover:text-gray-300 transition">{{ $pen }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="h-full flex flex-col items-center justify-center text-gray-500">
                                        <svg class="w-10 h-10 mb-2 opacity-20 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        <p class="text-sm">Todos enviaram!</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
                
                <div class="bg-slate-800/50 p-4 border-t border-slate-700 flex justify-end shrink-0">
                    <button wire:click="fecharModal" class="bg-slate-700 hover:bg-slate-600 text-white px-6 py-2 rounded-lg text-sm font-bold transition shadow-lg">Fechar</button>
                </div>
            </div>
        </div>
    @endif
</div>