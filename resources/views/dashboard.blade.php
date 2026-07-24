@extends('layouts.app')

@section('title', 'Dashboard Principal')

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
.fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

</style>

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@endpush

@section('content')

{{-- ============================================================
     HEADER — Padrão CONNECT (Seção 14.1)
     ============================================================ --}}
<x-page-header 
    title="Dashboard" 
    subtitle="Visão Geral de Apontamentos"
    icon="fas fa-chart-line"
    iconBg="from-indigo-500 to-indigo-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-7xl mx-auto w-full px-4 sm:px-6 flex justify-end mb-3 -mt-6"> 
    <x-notificacoes-bell />
</div>

<div class="max-w-7xl mx-auto w-full px-4 sm:px-6">

    {{-- ============================================================
         CARDS DE RESUMO (KPIs)
         ============================================================ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in">
        
        {{-- Card 1: Projetos / Clientes --}}
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden group hover:border-indigo-500/30 transition-all flex flex-col justify-between">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500 transition-all group-hover:w-2"></div>
            <div>
                <p class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-4 flex justify-between items-center">
                    <span>Projetos / Clientes</span>
                    <i class="fas fa-briefcase text-slate-600"></i>
                </p>
                <h3 class="text-3xl font-bold text-white tracking-tight">{{ $totalProjetos ?? 0 }}</h3>
                <p class="text-[10px] text-gray-500 uppercase mt-1">Projetos Ativos</p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-800">
                <a href="{{ route('projetos.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold flex items-center gap-1">
                    Visualizar Projetos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        {{-- Card 2: Colaboradores (Principal) --}}
        <div class="bg-slate-900 rounded-xl border border-indigo-500/50 p-6 shadow-lg shadow-indigo-900/20 relative overflow-hidden group hover:border-blue-500/50 transition-all flex flex-col justify-between">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500 transition-all group-hover:w-2"></div>
            <div class="absolute top-4 right-4">
                <span class="bg-indigo-500 text-white text-[10px] font-bold uppercase tracking-wider py-1 px-2 rounded shadow-lg">
                    Principal
                </span>
            </div>
            <div>
                <p class="text-xs text-blue-400 font-bold uppercase tracking-wider mb-4 flex justify-between items-center pr-16">
                    <span>Colaboradores</span>
                    <i class="fas fa-users text-slate-600"></i>
                </p>
                <h3 class="text-3xl font-bold text-white tracking-tight">{{ $totalColaboradores ?? 0 }}</h3>
                <p class="text-[10px] text-gray-500 uppercase mt-1">Ativos no mês</p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-800">
                <a href="{{ route('colaboradores.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-bold flex items-center gap-1">
                    Analisar Colaboradores <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        {{-- Card 3: Gestão / Alertas --}}
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden group hover:border-orange-500/30 transition-all flex flex-col justify-between">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-500 transition-all group-hover:w-2"></div>
            <div>
                <p class="text-xs text-orange-400 font-bold uppercase tracking-wider mb-4 flex justify-between items-center">
                    <span>Gestão / Alertas</span>
                    <i class="fas fa-chart-bar text-slate-600"></i>
                </p>
                <h3 class="text-3xl font-bold text-white tracking-tight">{{ $alertasGestao ?? 0 }}</h3>
                <p class="text-[10px] text-gray-500 uppercase mt-1">Apontamentos Divergentes/Análise</p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-800">
                <a href="{{ route('aprovacoes.dashboard') }}" class="text-xs text-orange-400 hover:text-orange-300 font-bold flex items-center gap-1">
                    Visualizar Gestão <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================
         GRID SECUNDÁRIO E RANKING
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        {{-- Área de Ranking (1 coluna) --}}
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-4 shadow-lg flex flex-col h-full relative overflow-hidden fade-in">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-purple-500"></div>
            <h3 class="text-purple-400 font-bold mb-4 text-sm uppercase tracking-wider pl-2 border-b border-slate-800 pb-2">
                Ranking Top 5 Horas
            </h3>
            
            <div class="flex-1 px-2 flex flex-col gap-2">
                @if(isset($rankingColaboradores) && $rankingColaboradores->count() > 0)
                    @foreach($rankingColaboradores as $index => $colab)
                    <div class="flex items-center justify-between text-sm py-2 border-b border-slate-800/50 last:border-0 hover:bg-slate-800/50 px-2 rounded-lg transition-colors">
                        <span class="text-slate-300 font-medium flex items-center gap-2">
                            <span class="text-purple-500 font-bold text-xs bg-purple-500/10 px-2 py-0.5 rounded">{{ $loop->iteration }}º</span>
                            {{ \Illuminate\Support\Str::limit($colab->nome_completo, 15) }}
                        </span>
                        <span class="font-bold text-purple-400">{{ number_format($colab->total_horas, 1, ',', '.') }}h</span>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-slate-500 text-sm py-8 flex flex-col items-center justify-center">
                        <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                        Nenhum apontamento neste mês.
                    </div>
                @endif
            </div>
        </div>

        {{-- Área Principal Complementar (3 colunas) --}}
        <div class="lg:col-span-3 bg-slate-900 rounded-xl border border-slate-800 flex flex-col shadow-lg relative overflow-hidden min-h-[400px] fade-in">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
            <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-900 z-10 pl-6">
                <div>
                    <h3 class="text-white font-bold text-lg uppercase tracking-wider">Detalhamento de Métricas</h3>
                    <p class="text-xs text-gray-500">Visualização de apontamentos diários e status</p>
                </div>
            </div>
            
            <div class="flex-1 p-6 flex items-center justify-center text-slate-600 bg-slate-900/50">
                <div class="text-center">
                    <i class="fas fa-chart-area text-4xl mb-3 opacity-50"></i>
                    <p class="text-sm">Área reservada para gráficos detalhados e tabelas de apontamentos.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
