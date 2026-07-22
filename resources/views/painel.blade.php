@extends('layouts.app')

@section('title', 'Painel de Módulos')

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
    $relacaoColaborador = auth()->user()->colaborador; 
    $nivelAcesso = $relacaoColaborador ? strtoupper($relacaoColaborador->nivel_acesso) : 'OPERACIONAL';
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
                <a href="{{ route('home') }}"
                   class="p-2 rounded-lg hover:bg-slate-700/50 text-slate-400 hover:text-white transition"
                   title="Voltar ao Início">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-rose-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-project-diagram text-white text-lg"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold text-white">Planejamento</h1>
                        <p class="text-xs theme-text-muted">Gestão de Projetos</p>
                    </div>
                </div>
            </div>

            {{-- Lado direito: Theme Toggle + Usuário + Logout --}}
            <div class="flex items-center gap-2 sm:gap-4">

                {{-- Toggle de Tema (conforme seção 5.10 e 14.1) --}}
                <div class="hidden sm:flex items-center gap-2 sm:gap-4">
                    <x-theme-toggle />
                </div>

                {{-- Dados do Usuário --}}
                <div class="flex items-center gap-4 shrink-0 justify-end">
                    <x-user-info />
                </div>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit"
                            class="p-2 rounded-lg hover:bg-red-500/20 text-slate-400 hover:text-red-400 transition"
                            title="Sair">
                        <i class="fas fa-sign-out-alt text-lg text-white"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto w-full px-0 sm:px-4 lg:px-6">

    {{-- ============================================================
         14.2 HERO SECTION (Título Flutuante)
         Classe .welcome-container com animação float
         ============================================================ --}}
    <div class="welcome-container text-center mb-6 sm:mb-8 lg:mb-12">

        <h1 class="text-xl sm:text-3xl lg:text-4xl font-bold mb-2 sm:mb-4 text-white">
            <i class="fas fa-project-diagram text-rose-500 mr-3"></i>
            Módulo <span class="text-rose-500">Planejamento</span>
        </h1>

        <p class="text-sm sm:text-lg lg:text-xl text-slate-400">
            Gestão de projetos e planejamento
        </p>

    </div>

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

            {{-- Card: Aprovações (apenas Gestores, Admins e Gerenciais) --}}
            @if(in_array($nivelAcesso, ['GESTOR', 'ADMIN', 'GERENCIAL']))
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
            @endif
        </div>
    </div>

    {{-- ============================================================
         CATEGORIA 2 — GESTÃO (apenas Owner)
         ============================================================ --}}
    @if($nivelAcesso === 'ADMIN')
    <div class="mb-8">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2 theme-text-primary">
            <i class="fas fa-tasks text-rose-500"></i>
            Gestão
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            {{-- Card: Controle de Envios --}}
            <a href="{{ route('conformidade.dashboard') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-green-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-green-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-check-circle text-green-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Controle de Envios</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Monitor de horas, métricas diárias e notificações de pendências.</p>
                    
                    <div class="flex items-center text-green-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Dashboard --}}
            <a href="/api/dashboard" target="_blank" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-blue-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-chart-pie text-blue-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Dashboard</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Painel JSON estático para consumo rápido de KPIs de uso.</p>
                    
                    <div class="flex items-center text-blue-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Logs do Sistema --}}
            <a href="{{ route('owner.auditoria') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-purple-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-purple-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-history text-purple-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Logs do Sistema</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Histórico de atividades e auditoria.</p>
                    
                    <div class="flex items-center text-purple-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
    @endif

    {{-- ============================================================
         CATEGORIA 3 — CONFIGURAÇÕES (apenas Owner)
         ============================================================ --}}
    @if($is_owner)
    <div class="mb-8">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2 theme-text-primary">
            <i class="fas fa-cog text-rose-500"></i>
            Configurações
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">

            {{-- Card: Health Check --}}
            <a href="{{ route('configuracoes.health') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-rose-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-rose-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-heartbeat text-rose-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Health Check</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Status dos servidores, banco de dados e APIs.</p>
                    
                    <div class="flex items-center text-rose-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Automação WhatsApp --}}
            <a href="{{ route('whatsapp.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-emerald-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-emerald-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fab fa-whatsapp text-emerald-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">WhatsApp</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Gestão do serviço de lembrete de ponto.</p>
                    
                    <div class="flex items-center text-emerald-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Espelho de Ponto --}}
            <a href="{{ route('pontos.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-sky-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-sky-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-file-invoice text-sky-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Espelho de Ponto</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Consulta dos espelhos de ponto importados pela integração Sólides.</p>
                    
                    <div class="flex items-center text-sky-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Feriados --}}
            <a href="{{ route('feriados.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-orange-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-orange-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-calendar-alt text-orange-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Feriados</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Cadastro de feriados nacionais e locais para bloqueio de horas.</p>
                    
                    <div class="flex items-center text-orange-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
    @endif

    {{-- ============================================================
         CATEGORIA 4 — MOVIMENTAÇÕES (apenas Owner)
         ============================================================ --}}
    @if($is_owner)
    <div class="mb-8">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2 theme-text-primary">
            <i class="fas fa-chart-line text-rose-500"></i>
            Movimentações
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            {{-- Card: Colaboradores --}}
            <a href="{{ route('colaboradores.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-orange-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-orange-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-users text-orange-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Gestão de Colaboradores</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Cadastro de funcionários e histórico de alterações.</p>
                    
                    <div class="flex items-center text-orange-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Gestão de Veículos --}}
            <a href="{{ route('veiculos.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-teal-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-teal-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-car text-teal-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Gestão de Veículos</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Controle da frota ativa.</p>
                    
                    <div class="flex items-center text-teal-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Gestão de Obras/Projetos --}}
            <a href="{{ route('projetos.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-purple-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-purple-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-building text-purple-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Gestão de Projetos</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Cadastro de projetos e vínculo com colaboradores.</p>
                    
                    <div class="flex items-center text-purple-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            {{-- Card: Gestão de Setores --}}
            <a href="{{ route('setores.index') }}" class="module-card relative theme-bg-card rounded-xl border border-slate-700 p-4 lg:p-6 hover:border-cyan-500/50 transition group">
                <div class="relative z-10">
                    <div class="module-icon w-12 h-12 lg:w-14 lg:h-14 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-3 lg:mb-4 transition">
                        <i class="fas fa-layer-group text-cyan-500 text-xl lg:text-2xl"></i>
                    </div>
                    <h3 class="text-base lg:text-lg font-bold mb-1 lg:mb-2 theme-text-primary">Gestão de Setores</h3>
                    <p class="text-xs lg:text-sm theme-text-secondary mb-3 lg:mb-4 line-clamp-2">Gerenciamento dos setores da empresa.</p>
                    
                    <div class="flex items-center text-cyan-500 group-hover:opacity-80 transition">
                        <span class="text-xs lg:text-sm font-medium">Acessar</span>
                        <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
    @endif

    {{-- ============================================================
         14.4 BOTÃO DE AÇÃO INFERIOR (Voltar Geral)
         Classes exatas: inline-flex items-center gap-2 px-6 py-3
         bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition
         ============================================================ --}}
    <div class="text-center mt-8">
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Início
        </a>
    </div>

</div>

@endsection
