@extends('layouts.app')

@section('title', 'Gestão de Timesheet')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@push('head')
<style>
/* ==========================================================
   Card de Navegação — Seção 15 do Design System
   ========================================================== */

.module-card {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
}

.module-card:hover:not(.disabled) {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,.3);
}

.module-card::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 1rem;
    background: linear-gradient(
        135deg,
        rgba(255,255,255,.1) 0%,
        transparent 50%
    );
    pointer-events: none;
}

/* ==========================================================
   Ícones — Seção 15
   ========================================================== */

.module-icon {
    transition: transform .3s ease;
}

.module-card:hover:not(.disabled) .module-icon {
    transform: scale(1.1) rotate(5deg);
}

/* ==========================================================
   Estado Desabilitado — Seção 15
   ========================================================== */

.module-card.disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* ==========================================================
   Header — Seção 15
   ========================================================== */

.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}

/* ==========================================================
   Hero Animation — Seção 15
   ========================================================== */

@keyframes float {
    0%,100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.welcome-container {
    animation: float 3s ease-in-out infinite;
}

/* ==========================================================
   Utilitário — Seção 15
   ========================================================== */

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ==========================================================
   Responsividade — Seção 15
   ========================================================== */

@media (max-width:767px){
    .module-card{
        padding:0.5rem!important;
    }
    .module-card:hover:not(.disabled){
        transform:translateY(-4px) scale(1.01);
    }
    .module-icon{
        width:2.5rem!important;
        height:2.5rem!important;
    }
    .module-icon i{
        font-size:1rem!important;
    }
    .module-card h3{
        font-size:.9rem!important;
    }
    .module-card p{
        font-size:.75rem!important;
    }
}

@media (max-width:575px){
    .welcome-title{
        font-size:1.25rem!important;
    }
    .welcome-subtitle{
        font-size:.875rem!important;
    }
}

</style>

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
@php
    $user = auth()->user();
    $relacaoColaborador = $user->colaborador; 
    $nivelAcesso = \App\Helpers\AcessoHelper::isAdmin($user) ? 'ADMIN' : strtoupper($user->roles->first()?->name ?? 'OPERACIONAL');
@endphp

{{-- ============================================================
     14.1 HEADER CLEAN DE MÓDULO (Com Navegação e Perfil)
     Classes exatas do Design System: header-gradient, border-b,
     theme-border, sticky top-0 z-50, backdrop-blur-lg
     ============================================================ --}}
<header class="header-gradient border-b border-slate-700/50 sticky top-0 z-50 backdrop-blur-lg -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-8 lg:-mt-8 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center justify-between">

            {{-- Lado esquerdo: Voltar + Identificação do módulo --}}
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('painel') }}"
                   class="p-2 rounded-lg hover:bg-slate-700/50 text-slate-400 hover:text-white transition"
                   title="Voltar ao Planejamento">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-rose-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-white text-lg"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold text-white">Gestão de Timesheet</h1>
                        <p class="text-xs theme-text-muted">Gestão de Horas e Aprovações</p>
                    </div>
                </div>
            </div>

            {{-- Lado direito: Theme Toggle + Usuário + Logout --}}
            <div class="flex items-center gap-2 sm:gap-4">

                {{-- Botão de Treinamento --}}
                <a href="{{ route('treinamento.index') }}"
                   class="flex items-center justify-center p-2 rounded-lg hover:bg-slate-700/50 text-slate-400 hover:text-rose-500 transition"
                   title="Treinamento">
                    <i class="fas fa-graduation-cap text-lg"></i>
                </a>

                {{-- Toggle de Tema (conforme seção 5.10 e 14.1) --}}
                <div class="hidden sm:flex items-center gap-2 sm:gap-4">
                    <x-theme-toggle />
                </div>

                {{-- Dados do Usuário --}}
                <div class="flex items-center gap-4 shrink-0 justify-end">
                    <x-user-info />
                </div>
            </div>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto w-full px-0 sm:px-4 lg:px-6">
    {{-- ============================================================
         CATEGORIA 1 — TIMESHEET (Visível para TODOS)
         ============================================================ --}}
    <div class="mb-8">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2 theme-text-primary">
            <i class="fas fa-clock text-rose-500"></i>
            Timesheet
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            {{-- Card: Apontamento de Timesheet --}}
            <a href="{{ route('apontamentos.create') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-indigo-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-indigo-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-stopwatch text-indigo-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Apontamento de Horas</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Registro diário de horas trabalhadas.</p>
                    
                    <div class="flex items-center text-indigo-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Controle de Apontamentos --}}
            <a href="{{ route('historico.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-blue-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-list text-blue-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Histórico de Apontamentos</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Visualise todos os apontamentos de horas.</p>
                    
                    <div class="flex items-center text-blue-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Aprovações (apenas Coordenadores, Admins e Gerenciais) --}}
            @hasanyrole('ADMIN|GERENCIAL|COORDENADOR')
            <a href="{{ route('aprovacoes.dashboard') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-yellow-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-yellow-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-file-signature text-yellow-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Aprovações</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Análise e aprovação de apontamentos.</p>
                    
                    <div class="flex items-center text-yellow-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
            @endhasanyrole

            {{-- Card: Relatórios --}}
            @hasanyrole('ADMIN|SAC|GERENCIAL')
            <a href="{{ route('relatorios.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-emerald-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-file-excel text-emerald-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Relatórios</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Extração de relatórios por período.</p>
                    
                    <div class="flex items-center text-emerald-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
            @endhasanyrole
        </div>
    </div>

    {{-- ============================================================
         BOTÃO DE AÇÃO INFERIOR (Voltar Geral)
         ============================================================ --}}
    <div class="text-center mt-8">
        <a href="{{ route('painel') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Planejamento
        </a>
    </div>

</div>

@endsection
