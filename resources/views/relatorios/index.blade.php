@extends('layouts.app')

@section('title', 'Extração de Relatórios')

@push('head')
<style>
/* ==========================================================
   Header — Seção 15 do Design System
   ========================================================== */
.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}
</style>

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    title="Relatórios" 
    subtitle="Extração de dados por período"
    icon="fas fa-file-excel"
    iconColor="text-emerald-400"
    backUrl="{{ route('timesheet.index') }}">
</x-page-header>

<div class="w-full space-y-4 md:space-y-6">

    {{-- ============================================================
         BLOCO 1: FILTROS DE EXTRAÇÃO
         Card padrão: bg-slate-800 rounded-xl border border-slate-700/50
         Mobile-first: p-4, grids 1-column
         ============================================================ --}}
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 md:p-6 mb-8">

        {{-- Título do card --}}
        <div class="flex items-center gap-3 mb-4 md:mb-5 pb-3 md:pb-4 border-b border-slate-700/50">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-slate-700 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-filter text-emerald-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-base md:text-lg font-bold text-white leading-tight">Filtros de Extração</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">Defina o período e o tipo de relatório desejado.</p>
            </div>
        </div>

        <form action="{{ route('relatorios.exportar') }}" method="POST" x-data="{ tipoRelatorio: '{{ request('tipo_relatorio', 'dorme_fora') }}' }">
            @csrf
            
            <input type="hidden" name="tipo_relatorio" x-model="tipoRelatorio">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-5 md:mb-6">
                
                {{-- Tipo de Relatório --}}
                <div class="md:col-span-2">
                    <label class="block text-xs md:text-sm font-medium mb-1.5 md:mb-2 text-slate-400">
                        Tipo de Relatório
                    </label>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Card Dorme Fora --}}
                        <div @click="tipoRelatorio = 'dorme_fora'"
                             :class="tipoRelatorio === 'dorme_fora' ? 'border-indigo-500 bg-indigo-500/10 shadow-lg shadow-indigo-500/10' : 'border-slate-700 bg-slate-900 hover:border-indigo-500/50'"
                             class="cursor-pointer rounded-xl border p-4 transition-all duration-200 flex items-center gap-4 group">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                                 :class="tipoRelatorio === 'dorme_fora' ? 'bg-indigo-500 text-white' : 'bg-slate-800 text-slate-400 group-hover:text-indigo-400'">
                                <i class="fas fa-moon text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold transition-colors" :class="tipoRelatorio === 'dorme_fora' ? 'text-indigo-400' : 'text-white'">Dorme Fora</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Relatório de pernoites.</p>
                            </div>
                        </div>

                        {{-- Card SEFIP --}}
                        <div @click="tipoRelatorio = 'sefip'"
                             :class="tipoRelatorio === 'sefip' ? 'border-sky-500 bg-sky-500/10 shadow-lg shadow-sky-500/10' : 'border-slate-700 bg-slate-900 hover:border-sky-500/50'"
                             class="cursor-pointer rounded-xl border p-4 transition-all duration-200 flex items-center gap-4 group">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                                 :class="tipoRelatorio === 'sefip' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-400 group-hover:text-sky-400'">
                                <i class="fas fa-file-invoice text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold transition-colors" :class="tipoRelatorio === 'sefip' ? 'text-sky-400' : 'text-white'">SEFIP</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Extração para SEFIP.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Data Inicial --}}
                <div>
                    <label for="data_inicial" class="block text-xs md:text-sm font-medium mb-1.5 md:mb-2 text-slate-400">
                        Data Inicial
                    </label>
                    <input type="date" id="data_inicial" name="data_inicial" value="{{ request('data_inicial') }}"
                           class="w-full px-3 py-2 md:px-4 md:py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition bg-slate-900 border-slate-700 text-white" required>
                </div>

                {{-- Data Final --}}
                <div>
                    <label for="data_final" class="block text-xs md:text-sm font-medium mb-1.5 md:mb-2 text-slate-400">
                        Data Final
                    </label>
                    <input type="date" id="data_final" name="data_final" value="{{ request('data_final') }}"
                           class="w-full px-3 py-2 md:px-4 md:py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition bg-slate-900 border-slate-700 text-white" required>
                </div>
            </div>

            {{-- Botões de Ação — Seção 5.6
                 MOBILE: flex-col-reverse
                 DESKTOP: flex-row --}}
            <div class="flex flex-col-reverse lg:flex-row gap-3">
                <button type="submit" formaction="{{ route('relatorios.index') }}" formmethod="GET"
                   class="w-full flex-1 flex items-center justify-center gap-2 px-4 py-3 h-12 text-sm md:text-base font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-500 active:bg-blue-700 transition shadow-lg shadow-blue-900/20">
                    <i class="fas fa-eye"></i>
                    Visualizar Relatório
                </button>
                <button type="submit" formtarget="_blank" x-show="tipoRelatorio === 'dorme_fora'"
                   class="w-full flex-1 flex items-center justify-center gap-2 px-4 py-3 h-12 text-sm md:text-base font-bold text-emerald-400 bg-slate-800 border border-emerald-500/30 rounded-lg hover:bg-slate-700 hover:border-emerald-500 active:bg-slate-800 transition shadow-lg">
                    <i class="fas fa-print"></i>
                    Exportar Relatório
                </button>
            </div>
        </form>

    </div>

    @if(isset($dadosRelatorio))
    {{-- ============================================================
         BLOCO 1.5: VISUALIZAÇÃO DOS RESULTADOS
         Card padrão: bg-slate-800 rounded-xl border border-slate-700/50
         ============================================================ --}}
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 md:p-6 mb-8">
        
        {{-- Título do card --}}
        <div class="flex items-center gap-3 mb-4 md:mb-5 pb-3 md:pb-4 border-b border-slate-700/50">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-slate-700 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-list text-blue-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-base md:text-lg font-bold text-white leading-tight">Resultados da Pesquisa</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">Visualização prévia do relatório filtrado.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            @if($tipoRelatorio === 'dorme_fora')
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-900/50 text-slate-400 border-b border-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 font-medium rounded-tl-lg">Colaborador</th>
                        <th class="px-4 py-3 font-medium">Cargo</th>
                        <th class="px-4 py-3 font-medium">Data</th>
                        <th class="px-4 py-3 font-medium">Código</th>
                        <th class="px-4 py-3 font-medium rounded-tr-lg">Local</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    @forelse ($dadosRelatorio as $nomeColaborador => $registros)
                        {{-- Linha de consolidação do colaborador --}}
                        <tr class="bg-slate-700/50">
                            <td colspan="5" class="px-4 py-3 font-bold text-emerald-400">
                                <i class="fas fa-user-check mr-2"></i> {{ $nomeColaborador }} - TOTAL: {{ $registros->count() }}
                            </td>
                        </tr>
                        
                        {{-- Linhas individuais --}}
                        @foreach($registros as $linha)
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-4 py-3 font-medium text-white">{{ $linha->nome_colaborador }}</td>
                                <td class="px-4 py-3 uppercase text-slate-300 font-semibold">{{ $linha->cargo }}</td>
                                <td class="px-4 py-3 uppercase text-slate-300 font-semibold">{{ $linha->data }}</td>
                                <td class="px-4 py-3 uppercase text-slate-300 font-semibold">{{ $linha->codigo_local }}</td>
                                <td class="px-4 py-3 uppercase text-slate-300 font-semibold">{{ $linha->nome_local }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 italic bg-slate-900/20">
                                <i class="fas fa-search text-2xl mb-2 text-slate-600"></i><br>
                                Nenhum registro encontrado para o período selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @elseif($tipoRelatorio === 'sefip')
            <form action="{{ route('relatorios.exportar') }}" method="POST" target="_blank">
                @csrf
                <input type="hidden" name="tipo_relatorio" value="sefip">
                <input type="hidden" name="data_inicial" value="{{ request('data_inicial') }}">
                <input type="hidden" name="data_final" value="{{ request('data_final') }}">
                
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase bg-slate-900/50 text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th class="px-4 py-3 font-medium rounded-tl-lg">Colaborador</th>
                            <th class="px-4 py-3 font-medium">Data</th>
                            <th class="px-4 py-3 font-medium rounded-tr-lg">Horas Totais</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @forelse ($dadosRelatorio as $chaveLocal => $registros)
                            @php
                                $partes = explode('|||', $chaveLocal);
                                $codigoLocal = $partes[0] ?? '';
                                $nomeLocal = $partes[1] ?? '';
                            @endphp
                            {{-- Linha de consolidação do local --}}
                            <tr class="bg-slate-700/50">
                                <td colspan="3" class="px-4 py-3 font-bold text-sm text-sky-500">
                                    <div class="flex flex-wrap items-center gap-6">
                                        <div>
                                            <i class="fas fa-map-marker-alt mr-2"></i>{{ $nomeLocal }} - {{ $codigoLocal }}
                                        </div>
                                        <div class="flex flex-wrap items-center gap-4">
                                            <div class="flex items-center gap-2">
                                                <label class="text-sm text-sky-500">CNPJ:</label>
                                                <input type="text" name="dados_manuais[{{ $chaveLocal }}][cnpj]" oninput="this.value = maskCNPJ(this.value)" maxlength="18" class="bg-slate-900 border border-slate-600 rounded px-2 py-1 text-white text-xs w-36 focus:border-sky-500 focus:outline-none" placeholder="00.000.000/0000-00">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="text-sm text-sky-500">RAZÃO SOCIAL:</label>
                                                <input type="text" name="dados_manuais[{{ $chaveLocal }}][razao_social]" class="bg-slate-900 border border-slate-600 rounded px-2 py-1 text-white text-xs w-64 md:w-80 focus:border-sky-500 focus:outline-none" placeholder="Digite a Razão Social...">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            {{-- Linhas individuais --}}
                            @foreach($registros as $linha)
                                <tr class="hover:bg-slate-700/30 transition-colors">
                                    <td class="px-4 py-3 font-medium text-white">{{ $linha->nome_colaborador }}</td>
                                    <td class="px-4 py-3 uppercase text-slate-300 font-semibold">{{ $linha->data }}</td>
                                    <td class="px-4 py-3 uppercase text-slate-300 font-semibold">{{ $linha->horas_totais }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500 italic bg-slate-900/20">
                                    <i class="fas fa-search text-2xl mb-2 text-slate-600"></i><br>
                                    Nenhum registro encontrado para o período selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @if($dadosRelatorio->isNotEmpty())
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-sky-600 rounded-lg hover:bg-sky-500 active:bg-sky-700 transition shadow-lg shadow-sky-900/20">
                        <i class="fas fa-print"></i>
                        Exportar SEFIP Preenchido
                    </button>
                </div>
                @endif
            </form>
            @endif
        </div>

    </div>
    @endif



</div>

<script>
    function maskCNPJ(value) {
        return value
            .replace(/\D/g, '')
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2')
            .substring(0, 18);
    }
</script>

@endsection
