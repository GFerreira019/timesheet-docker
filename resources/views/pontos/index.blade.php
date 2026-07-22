@extends('layouts.app')
@section('title', 'Controle de Ponto (Sólides)')

@push('head')
<style>
    .header-gradient {
        background: linear-gradient(
            135deg,
            rgba(30,41,59,.95) 0%,
            rgba(15,23,42,.98) 100%
        );
    }
    .table-row-alt:nth-child(even) { background: rgba(15,23,42,0.3); }
    .table-row-alt:nth-child(odd) { background: transparent; }
    
    select option {
        background-color: #1e293b !important;
        color: #ffffff !important;
    }
</style>
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

{{-- CABEÇALHO --}}
<x-page-header 
    title="Espelho de Ponto" 
    subtitle="Integração em tempo real com API Sólides"
    icon="fas fa-clock"
    iconBg="from-indigo-500 to-indigo-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6">
    
    {{-- ERROS --}}
    @if($errors->any())
        <div class="mb-6 bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg shadow-sm">
            <ul class="list-disc list-inside text-sm font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FILTROS (Formulário GET) --}}
    <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg mb-8">
        <form action="{{ route('pontos.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
            
            <div class="w-full sm:w-1/2">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Colaborador</label>
                <div class="relative">
                    <select name="colaborador_id" required class="w-full bg-slate-900 border border-slate-600 rounded-lg p-2.5 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer">
                        <option value="">Selecione um colaborador...</option>
                        @foreach($colaboradores as $colaborador)
                            <option value="{{ $colaborador->id }}" {{ $colaboradorId == $colaborador->id ? 'selected' : '' }}>
                                {{ $colaborador->nome_completo }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div class="w-full sm:w-1/4">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Mês/Ano</label>
                <input type="month" name="mes_ano" value="{{ $mesAno ?? date('Y-m') }}" required 
                       class="w-full bg-slate-900 border border-slate-600 rounded-lg p-2.5 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none uppercase">
            </div>

            <div class="w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-lg transition shadow-lg shadow-indigo-900/30 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    Buscar Ponto
                </button>
            </div>
        </form>
    </div>

    {{-- ESPELHO DE PONTO (Grid Inferior) --}}
    @if(isset($pontos))
        <div class="bg-slate-800 border border-slate-700/50 rounded-xl shadow-lg overflow-hidden">
            <div class="p-5 border-b border-slate-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user fa-fw text-indigo-400"></i>
                        <span>{{ $pontos['colaborador_nome'] ?? 'Colaborador Desconhecido' }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 flex items-center gap-3">
                        <i class="fas fa-id-badge fa-fw text-slate-400"></i>
                        <span>{{ $pontos['colaborador_cargo'] ?? '-' }}</span>
                    </p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center min-w-[900px]">
                    <thead class="bg-slate-900/50 text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th class="px-3 py-3 font-semibold text-left">Data</th>
                            <th class="px-3 py-3 font-semibold text-blue-400">Previstas</th>
                            <th class="px-3 py-3 font-semibold text-indigo-400">Trabalhadas</th>
                            <th class="px-3 py-3 font-semibold text-green-400">Abonadas</th>
                            <th class="px-2 py-3 font-semibold border-l border-slate-700/50">T1 - Início</th>
                            <th class="px-2 py-3 font-semibold">T1 - Fim</th>
                            <th class="px-2 py-3 font-semibold border-l border-slate-700/50">T2 - Início</th>
                            <th class="px-2 py-3 font-semibold">T2 - Fim</th>
                            <th class="px-2 py-3 font-semibold border-l border-slate-700/50">T3 - Início</th>
                            <th class="px-2 py-3 font-semibold">T3 - Fim</th>
                            <th class="px-2 py-3 font-semibold border-l border-slate-700/50">T4 - Início</th>
                            <th class="px-2 py-3 font-semibold">T4 - Fim</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs">
                        @forelse($pontos['registros'] ?? [] as $ponto)
                            <tr class="table-row-alt border-b border-slate-700/30 hover:bg-slate-700/20 transition group">
                                <td class="px-3 py-2 text-left">
                                    <p class="font-bold text-slate-200">
                                        {{ \Carbon\Carbon::parse($ponto['data'])->format('d/m/Y') }}
                                    </p>
                                    <p class="text-[9px] text-slate-500 uppercase">
                                        {{ \Carbon\Carbon::parse($ponto['data'])->translatedFormat('l') }}
                                    </p>
                                </td>
                                <td class="px-3 py-2 text-blue-300">{{ $ponto['previstas'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-indigo-300 font-bold">{{ $ponto['trabalhadas'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-green-300">{{ $ponto['abonadas'] ?? '-' }}</td>
                                
                                <td class="px-2 py-2 text-slate-400 border-l border-slate-700/30">{{ $ponto['t1_inicio'] ?? '-' }}</td>
                                <td class="px-2 py-2 text-slate-400">{{ $ponto['t1_fim'] ?? '-' }}</td>
                                
                                <td class="px-2 py-2 text-slate-400 border-l border-slate-700/30">{{ $ponto['t2_inicio'] ?? '-' }}</td>
                                <td class="px-2 py-2 text-slate-400">{{ $ponto['t2_fim'] ?? '-' }}</td>
                                
                                <td class="px-2 py-2 text-slate-400 border-l border-slate-700/30">{{ $ponto['t3_inicio'] ?? '-' }}</td>
                                <td class="px-2 py-2 text-slate-400">{{ $ponto['t3_fim'] ?? '-' }}</td>
                                
                                <td class="px-2 py-2 text-slate-400 border-l border-slate-700/30">{{ $ponto['t4_inicio'] ?? '-' }}</td>
                                <td class="px-2 py-2 text-slate-400">{{ $ponto['t4_fim'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-500">
                                        <i class="fas fa-folder-open text-4xl mb-3 text-slate-600"></i>
                                        <p class="font-medium text-sm text-slate-400">Nenhum registro encontrado para este período.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
