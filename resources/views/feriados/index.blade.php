@extends('layouts.app')
@section('title', 'Gestão de Cidades e Feriados')

@push('head')
<style>
.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}
.glow-orange { box-shadow: 0 0 20px rgba(251,146,60,0.15); }
.glow-green  { box-shadow: 0 0 20px rgba(34,197,94,0.10); }

/* Modal backdrop */
.modal-backdrop {
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
}

/* Shimmer animation for sync button */
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
.btn-shimmer:hover {
    background-size: 200% 100%;
    animation: shimmer 2s linear infinite;
    background-image: linear-gradient(90deg, rgba(251,146,60,0.1) 0%, rgba(251,146,60,0.3) 50%, rgba(251,146,60,0.1) 100%);
}

/* Tabela zebra */
.table-row-alt:nth-child(even) { background: rgba(15,23,42,0.3); }
.table-row-alt:nth-child(odd) { background: transparent; }

/* Força o fundo escuro e texto branco na lista de opções (dropdown nativo) */
select option {
    background-color: #1e293b !important;
    color: #ffffff !important;
}

</style>
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    title="Gestão de Cidades e Feriados" 
    subtitle="Monitoração inteligente de feriados por cidade dos colaboradores"
    icon="fas fa-calendar-alt"
    iconBg="from-orange-500 to-orange-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl py-2 sm:px-6 sm:py-1 mb-0 mx-auto overflow-x-hidden flex justify-end">
    <form action="{{ route('feriados.sincronizar') }}" method="POST" class="inline">
        @csrf
        <input type="hidden" name="ano" value="{{ $anoAtual }}">
        <button type="submit"
                class="btn-shimmer bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition w-full sm:w-auto shadow-lg shadow-orange-900/20 text-sm whitespace-nowrap border border-orange-500/50">
            <i class="fas fa-sync-alt"></i>
            <span class="hidden sm:inline">Sincronizar Tudo</span>
        </button>
    </form>
</div>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6 overflow-x-hidden">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-400 px-5 py-3 rounded-xl text-sm font-medium flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div class="whitespace-pre-line">{{ session('success') }}</div>
        </div>
    @endif

    {{-- ============================================================
         RESUMO SUPERIOR (Stats)
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        {{-- Total de Cidades --}}
        <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 flex items-center justify-center bg-blue-500/10 rounded-lg border border-blue-500/20">
                <i class="fas fa-map-marker-alt text-blue-400 text-lg"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $cidadesMonitoradas->count() }}</p>
                <p class="text-xs text-slate-400">Cidades Ativas</p>
            </div>
        </div>

        {{-- Cidades Atendidas --}}
        <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-12 h-12 flex items-center justify-center bg-green-500/10 rounded-lg border border-green-500/20">
                <i class="fas fa-check-double text-green-400 text-lg"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-400">{{ $cidadesAtendidas->count() }}</p>
                <p class="text-xs text-slate-400">Cidades com feriados cadastrados</p>
            </div>
        </div>

        {{-- Cidades Pendentes --}}
        <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-4 flex items-center gap-4 {{ $cidadesPendentes->count() > 0 ? 'border-amber-500/30' : '' }}">
            <div class="w-12 h-12 flex items-center justify-center bg-amber-500/10 rounded-lg border border-amber-500/20">
                <i class="fas fa-exclamation-triangle text-amber-400 text-lg"></i>
            </div>
            <div>
                <p class="text-2xl font-bold {{ $cidadesPendentes->count() > 0 ? 'text-amber-400' : 'text-white' }}">{{ $cidadesPendentes->count() }}</p>
                <p class="text-xs text-slate-400">Cidades sem feriados cadastrados</p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         GRID SUPERIOR (2 Colunas: Atendidas x Pendentes)
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        {{-- Card 1: Cidades Atendidas --}}
        <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg glow-green">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-700/50 pb-3">
                <div class="w-10 h-10 flex items-center justify-center bg-green-500/10 rounded-lg border border-green-500/20">
                    <i class="fas fa-city text-green-400"></i>
                </div>
                Cidades Cadastradas
                <span class="ml-auto text-xs font-normal text-green-400 bg-green-500/10 px-2.5 py-1 rounded-full border border-green-500/20">
                    {{ $cidadesAtendidas->count() }} cidade(s)
                </span>
            </h2>

            @if($cidadesAtendidas->count() > 0)
                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar pr-1">
                    @foreach($cidadesAtendidas as $local)
                        <div class="flex items-center gap-3 p-3 bg-slate-900/30 rounded-lg border border-slate-700/30 hover:border-green-500/20 transition group">
                            <div class="w-8 h-8 bg-green-500/10 rounded-full flex items-center justify-center flex-shrink-0 border border-green-500/20">
                                <i class="fas fa-check text-green-400 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-200">{{ $local->cidade }}</p>
                                <p class="text-[11px] text-slate-500">{{ $local->uf }} — Feriados municipais mapeados</p>
                            </div>
                            <span class="ml-auto text-[10px] bg-green-500/10 text-green-400 border border-green-500/20 px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition">
                                OK
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <i class="fas fa-info-circle text-slate-600 text-2xl mb-2"></i>
                    <p class="text-sm text-slate-500">Nenhuma cidade com feriados municipais cadastrados.</p>
                    <p class="text-xs text-slate-600 mt-1">Clique em "Sincronizar Tudo" para buscar automaticamente.</p>
                </div>
            @endif
        </div>

        {{-- Card 2: Ação Necessária — Cidades Pendentes --}}
        <div class="bg-slate-800 border {{ $cidadesPendentes->count() > 0 ? 'border-red-500/30' : 'border-slate-700/50' }} rounded-xl p-5 shadow-lg {{ $cidadesPendentes->count() > 0 ? 'glow-orange' : '' }}">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2 border-b {{ $cidadesPendentes->count() > 0 ? 'border-red-500/20' : 'border-slate-700/50' }} pb-3">
                <div class="w-10 h-10 flex items-center justify-center bg-red-500/10 rounded-lg border border-red-500/20">
                    <i class="fas fa-exclamation-circle {{ $cidadesPendentes->count() > 0 ? 'text-red-400' : 'text-slate-500' }}"></i>
                </div>
                <span class="{{ $cidadesPendentes->count() > 0 ? 'text-red-300' : '' }}">Ação Necessária</span>
                <span class="ml-auto text-xs font-normal {{ $cidadesPendentes->count() > 0 ? 'text-red-400 bg-red-500/10 border-red-500/20' : 'text-slate-400 bg-slate-700/30 border-slate-600/20' }} px-2.5 py-1 rounded-full border">
                    {{ $cidadesPendentes->count() }} pendência(s)
                </span>
            </h2>

            @if($cidadesPendentes->count() > 0)
                {{-- Alerta --}}
                <div class="bg-amber-500/5 border border-amber-500/20 rounded-lg p-3 mb-4">
                    <p class="text-xs text-amber-400 flex items-center gap-2">
                        <i class="fas fa-bell"></i>
                        Estas cidades possuem colaboradores ativos, mas nenhum feriado municipal cadastrado para {{ $anoAtual }}.
                    </p>
                </div>

                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar pr-1">
                    @foreach($cidadesPendentes as $local)
                        <div class="flex items-center gap-3 p-3 bg-slate-900/30 rounded-lg border border-red-500/10 hover:border-red-500/30 transition group">
                            <div class="w-8 h-8 bg-red-500/10 rounded-full flex items-center justify-center flex-shrink-0 border border-red-500/20">
                                <i class="fas fa-times text-red-400 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-200">{{ $local->cidade }}</p>
                                <p class="text-[11px] text-red-400/80">{{ $local->uf }} — Sem feriados municipais em {{ $anoAtual }}</p>
                            </div>
                            <button type="button"
                                    onclick="abrirModalManual('{{ $local->cidade }}', '{{ $local->uf }}')"
                                    class="ml-auto text-xs bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1.5 opacity-80 group-hover:opacity-100">
                                <i class="fas fa-plus-circle"></i>
                                Cadastrar Manualmente
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-3 border border-green-500/20">
                        <i class="fas fa-check-double text-green-400 text-xl"></i>
                    </div>
                    <p class="text-sm text-green-400 font-medium">Todas as cidades estão cobertas!</p>
                    <p class="text-xs text-slate-500 mt-1">Nenhuma pendência para {{ $anoAtual }}.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         GRID INFERIOR (Tabela de Feriados — Visão Agrupada + Planilha)
         ============================================================ --}}
    <div class="bg-slate-800 border border-slate-700/50 rounded-xl shadow-lg overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 border-b border-slate-700/50 gap-3">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <div class="w-10 h-10 flex items-center justify-center bg-orange-500/10 rounded-lg border border-orange-500/20">
                    <i class="fas fa-list text-orange-400"></i>
                </div>
                Feriados Cadastrados
                <span class="text-xs font-normal text-slate-400 bg-slate-700/30 px-2.5 py-1 rounded-full border border-slate-600/20 ml-2">
                    {{ $feriadosAgrupados->count() }} feriado(s) · {{ $feriados->count() }} registro(s)
                </span>
            </h2>
            <div class="flex flex-wrap items-center justify-end gap-2">
                {{-- Filtro de Cidade --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-filter text-slate-500 text-[10px]"></i>
                    </div>
                    <x-select-input 
                        id="filtro-cidade" 
                        onchange="filtrarPorCidade(this.value)"
                        class="pl-8 pr-8 py-1.5 rounded-lg border text-[11px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer max-w-[180px] sm:max-w-none transition"
                        style="background: var(--bg-secondary); border-color: var(--border-color); color: var(--text-primary);"
                    >
                        <option value="" style="background: var(--bg-secondary); color: var(--text-primary);">Todas as Cidades</option>
                        
                        @foreach($cidadesMonitoradas as $local)
                            <option value="{{ $local->cidade }}|{{ $local->uf }}" style="background: var(--bg-secondary); color: var(--text-primary);">
                                {{ $local->cidade }} / {{ $local->uf }}
                            </option>
                        @endforeach
                    </x-select-input>
                </div>

                {{-- Alternador de Visualização --}}
                <div class="flex bg-slate-900/50 rounded-lg border border-slate-700/50 p-0.5">
                    <button type="button" id="btn-view-agrupado" onclick="trocarVisao('agrupado')"
                            class="text-[11px] font-bold px-3 py-1.5 rounded-md transition bg-orange-500/20 text-orange-400 border border-orange-500/30">
                        <i class="fas fa-layer-group mr-1"></i> Agrupado
                    </button>
                    <button type="button" id="btn-view-planilha" onclick="trocarVisao('planilha')"
                            class="text-[11px] font-bold px-3 py-1.5 rounded-md transition text-slate-400 hover:text-white">
                        <i class="fas fa-table mr-1"></i> Planilha
                    </button>
                </div>
                <button type="button"
                        onclick="abrirModalManual('', '')"
                        class="px-4 py-2 text-xs font-bold border border-slate-600 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition shadow-sm flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Adicionar Feriado
                </button>
            </div>
        </div>

        {{-- =============== VISÃO AGRUPADA =============== --}}
        <div id="visao-agrupado" class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-900/50 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-700/50">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Data</th>
                        <th class="px-5 py-3 font-semibold">Descrição</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Cidades Cobertas</th>
                        <th class="px-5 py-3 font-semibold">Origem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feriadosAgrupados as $grupo)
                        <tr class="table-row-alt border-b border-slate-700/30 hover:bg-slate-700/20 transition group">
                            <td class="px-5 py-3">
                                <div>
                                    <p class="font-bold text-slate-200">
                                        {{ \Carbon\Carbon::parse($grupo->data)->format('d/m/Y') }}
                                    </p>
                                    <p class="text-[10px] text-slate-500 uppercase">
                                        {{ \Carbon\Carbon::parse($grupo->data)->translatedFormat('l') }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-300 font-medium">{{ $grupo->descricao }}</td>
                            <td class="px-5 py-3">
                                @if($grupo->tipo === 'nacional')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-globe-americas text-[8px]"></i> Nacional
                                    </span>
                                @elseif($grupo->tipo === 'estadual')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-map text-[8px]"></i> Estadual
                                    </span>
                                @elseif($grupo->tipo === 'municipal')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-city text-[8px]"></i> Municipal
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-slate-700/50 text-slate-300 border border-slate-600/30 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-question text-[8px]"></i> —
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    @foreach($grupo->cidades->take(5) as $cidadeUf)
                                        <span class="text-[10px] bg-slate-900/50 text-slate-300 border border-slate-700/50 px-1.5 py-0.5 rounded">
                                            {{ $cidadeUf }}
                                        </span>
                                    @endforeach
                                    @if($grupo->cidades->count() > 5)
                                        <span class="text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/20 px-1.5 py-0.5 rounded font-bold">
                                            +{{ $grupo->cidades->count() - 5 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if($grupo->manual)
                                    <span class="text-[10px] bg-slate-700/50 text-slate-300 border border-slate-600/30 px-2 py-0.5 rounded-full">Manual</span>
                                @else
                                    <span class="text-[10px] bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 px-2 py-0.5 rounded-full">API</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-calendar-xmark text-slate-600 text-3xl"></i>
                                    <p class="text-sm text-slate-500 font-medium">Nenhum feriado cadastrado.</p>
                                    <p class="text-xs text-slate-600">Clique em "Sincronizar Tudo" ou "Adicionar Feriado" para começar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- =============== VISÃO PLANILHA (Flat — igual à planilha legada) =============== --}}
        <div id="visao-planilha" class="overflow-x-auto hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-900/50 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-700/50">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Data</th>
                        <th class="px-5 py-3 font-semibold">Descrição</th>
                        <th class="px-5 py-3 font-semibold">Cidade/UF</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Origem</th>
                        <th class="px-5 py-3 font-semibold text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feriados as $feriado)
                        <tr class="table-row-alt border-b border-slate-700/30 hover:bg-slate-700/20 transition group planilha-row" 
                            data-cidade="{{ $feriado->cidade }}|{{ $feriado->uf }}">
                            <td class="px-5 py-3">
                                <div>
                                    <p class="font-bold text-slate-200">
                                        {{ \Carbon\Carbon::parse($feriado->data)->format('d/m/Y') }}
                                    </p>
                                    <p class="text-[10px] text-slate-500 uppercase">
                                        {{ \Carbon\Carbon::parse($feriado->data)->translatedFormat('l') }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-5 py-2.5 text-slate-300">{{ $feriado->descricao }}</td>
                            <td class="px-5 py-2.5">
                                <span class="text-[11px] bg-slate-900/50 text-slate-300 border border-slate-700/50 px-1.5 py-0.5 rounded">
                                    {{ $feriado->cidade }}/{{ $feriado->uf }}
                                </span>
                            </td>
                            <td class="px-5 py-2.5">
                                @if($feriado->tipo === 'nacional')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-globe-americas text-[8px]"></i> Nacional
                                    </span>
                                @elseif($feriado->tipo === 'estadual')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-map text-[8px]"></i> Estadual
                                    </span>
                                @elseif($feriado->tipo === 'municipal')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-city text-[8px]"></i> Municipal
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[10px] bg-slate-700/50 text-slate-300 border border-slate-600/30 px-2 py-0.5 rounded-full font-bold">
                                        <i class="fas fa-question text-[8px]"></i> —
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5">
                                @if($feriado->inserido_manualmente)
                                    <span class="text-[10px] bg-slate-700/50 text-slate-300 border border-slate-600/30 px-2 py-0.5 rounded-full">Manual</span>
                                @else
                                    <span class="text-[10px] bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 px-2 py-0.5 rounded-full">API</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right">
                                <form action="{{ route('feriados.deletar', $feriado->id) }}" method="POST"
                                      onsubmit="return confirm('Excluir este registro de {{ $feriado->descricao }} ({{ $feriado->cidade }}/{{ $feriado->uf }})?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-slate-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition opacity-0 group-hover:opacity-100 focus:opacity-100"
                                            title="Excluir">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-calendar-xmark text-slate-600 text-3xl"></i>
                                    <p class="text-sm text-slate-500 font-medium">Nenhum feriado cadastrado.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ============================================================
     MODAL: Cadastro Manual de Feriado
     ============================================================ --}}
<div id="modal-feriado-manual" class="fixed inset-0 z-[100] hidden">
    {{-- Backdrop --}}
    <div class="modal-backdrop absolute inset-0" onclick="fecharModalManual()"></div>

    {{-- Conteúdo --}}
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-slate-800 border border-slate-700/50 rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden">
            {{-- Header do Modal --}}
            <div class="bg-slate-900/50 border-b border-slate-700/50 p-5 flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <div class="bg-orange-500/10 p-1.5 rounded-lg">
                        <i class="fas fa-calendar-plus text-orange-400"></i>
                    </div>
                    Cadastrar Feriado Manual
                </h3>
                <button type="button" onclick="fecharModalManual()"
                        class="p-2 hover:bg-slate-700/50 rounded-lg text-slate-400 hover:text-white transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body --}}
            <form action="{{ route('feriados.manual') }}" method="POST" class="p-5 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-wider text-slate-400 mb-1.5 font-semibold">Data *</label>
                        <input type="date" name="data" required
                               class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-wider text-slate-400 mb-1.5 font-semibold">Descrição *</label>
                        <input type="text" name="descricao" required placeholder="Ex: Dia do Padroeiro"
                               class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-wider text-slate-400 mb-1.5 font-semibold">CIDADE / UF *</label>
                    <select name="localidade" id="modal-localidade" required 
                            class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none transition appearance-none">
                        <option value="">Selecione a localidade...</option>
                        @foreach($cidadesMonitoradas as $local)
                            <option value="{{ $local->cidade }}|{{ $local->uf }}">
                                {{ $local->cidade }} / {{ $local->uf }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="fecharModalManual()"
                            class="px-4 py-2 text-sm text-slate-400 hover:text-white border border-slate-700 rounded-lg hover:bg-slate-700/50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="bg-orange-600 hover:bg-orange-500 text-white text-sm font-bold py-2 px-5 rounded-lg transition shadow-lg shadow-orange-900/20 flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        Salvar Feriado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Filtro por cidade
function filtrarPorCidade(valor) {
    if (valor) {
        trocarVisao('planilha');
        document.getElementById('btn-view-agrupado').classList.add('opacity-50', 'pointer-events-none');
    } else {
        document.getElementById('btn-view-agrupado').classList.remove('opacity-50', 'pointer-events-none');
    }

    const rows = document.querySelectorAll('.planilha-row');
    
    rows.forEach(row => {
        if (!valor || row.getAttribute('data-cidade') === valor) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Alternador de visão (Agrupado x Planilha)
function trocarVisao(visao) {
    const agrupado = document.getElementById('visao-agrupado');
    const planilha = document.getElementById('visao-planilha');
    const btnAgrupado = document.getElementById('btn-view-agrupado');
    const btnPlanilha = document.getElementById('btn-view-planilha');

    if (visao === 'planilha') {
        agrupado.classList.add('hidden');
        planilha.classList.remove('hidden');
        btnPlanilha.className = 'text-[11px] font-bold px-3 py-1.5 rounded-md transition bg-orange-500/20 text-orange-400 border border-orange-500/30';
        btnAgrupado.className = 'text-[11px] font-bold px-3 py-1.5 rounded-md transition text-slate-400 hover:text-white';
    } else {
        planilha.classList.add('hidden');
        agrupado.classList.remove('hidden');
        btnAgrupado.className = 'text-[11px] font-bold px-3 py-1.5 rounded-md transition bg-orange-500/20 text-orange-400 border border-orange-500/30';
        btnPlanilha.className = 'text-[11px] font-bold px-3 py-1.5 rounded-md transition text-slate-400 hover:text-white';
    }
}

function abrirModalManual(cidade, uf) {
    const modal = document.getElementById('modal-feriado-manual');
    const selectLocalidade = document.getElementById('modal-localidade');

    if (selectLocalidade && cidade && uf) {
        selectLocalidade.value = cidade + '|' + uf;
    } else if (selectLocalidade) {
        selectLocalidade.value = '';
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function fecharModalManual() {
    const modal = document.getElementById('modal-feriado-manual');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModalManual();
});
</script>
@endpush

@endsection
