@extends('layouts.app')

@section('title', 'Suporte do Sistema - Gestão de Tickets')

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@section('content')

{{-- ============================================================
     HEADER
     ============================================================ --}}

<header class="header-gradient border-b border-slate-700/50 sticky top-0 z-50 backdrop-blur-lg -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-8 lg:-mt-8 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex items-center justify-between">

            {{-- Lado esquerdo: Voltar + Identificação do módulo --}}
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('painel') }}"
                   class="p-2 rounded-lg hover:bg-slate-700/50 text-white hover:text-white transition"
                   title="Voltar ao Início">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-headset text-white text-lg"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold text-white">Suporte do Sistema</h1>
                        <p class="text-xs theme-text-muted">Gestão de Tickets</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <button onclick="abrirModalNovoTicket()" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition flex items-center gap-2 text-white">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">Novo Ticket</span>
                </button>


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
                </div>
            </div>
        </div>
    </div>
</header>

<div class="mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    
    {{-- ============================================================
         CARDS DE STATUS (Contadores)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5" id="contadoresSection">
        {{-- Card: Abertos --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700/50 p-4 cursor-pointer hover:border-blue-500/50 dark:hover:border-blue-500/50 transition group" onclick="filtrarPorStatus('ABERTO')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold mb-1">Abertos</p>
                    <p class="text-2xl sm:text-3xl font-bold text-blue-500 dark:text-blue-400" id="cntAbertos">{{ $countAbertos }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-500/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-envelope-open text-blue-500 dark:text-blue-400"></i>
                </div>
            </div>
        </div>
        
        {{-- Card: Em Andamento --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700/50 p-4 cursor-pointer hover:border-yellow-500/50 dark:hover:border-yellow-500/50 transition group" onclick="filtrarPorStatus('EM_ANDAMENTO')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold mb-1">Em Andamento</p>
                    <p class="text-2xl sm:text-3xl font-bold text-yellow-500 dark:text-yellow-400" id="cntAndamento">{{ $countEmAndamento }}</p>
                </div>
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-500/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-spinner text-yellow-500 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>
        
        {{-- Card: Aguardando --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700/50 p-4 cursor-pointer hover:border-purple-500/50 dark:hover:border-purple-500/50 transition group" onclick="filtrarPorStatus('AGUARDANDO')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold mb-1">Aguardando</p>
                    <p class="text-2xl sm:text-3xl font-bold text-purple-500 dark:text-purple-400" id="cntAguardando">{{ $countAguardando }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-500/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-clock text-purple-500 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
        
        {{-- Card: Fechados --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700/50 p-4 cursor-pointer hover:border-green-500/50 dark:hover:border-green-500/50 transition group" onclick="filtrarPorStatus('FECHADO')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold mb-1">Fechados</p>
                    <p class="text-2xl sm:text-3xl font-bold text-green-500 dark:text-green-400" id="cntFechados">{{ $countFechados }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-500/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BARRA DE FILTROS
         ============================================================ --}}
    <form method="GET" action="{{ route('suporte.index') }}" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700/50 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" id="filtBusca" placeholder="Buscar por título, protocolo ou descrição..." 
                       value="{{ request('search') }}"
                       class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-800 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition" 
                       onchange="this.form.submit()">
            </div>
            <div class="flex gap-2">
                <select name="status" id="filtStatus" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-sm text-slate-800 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition cursor-pointer" onchange="this.form.submit()">
                    <option value="">Todos os Status</option>
                    <option value="ABERTO" {{ request('status') == 'ABERTO' ? 'selected' : '' }}>Abertos</option>
                    <option value="EM_ANDAMENTO" {{ request('status') == 'EM_ANDAMENTO' ? 'selected' : '' }}>Em Andamento</option>
                    <option value="AGUARDANDO" {{ request('status') == 'AGUARDANDO' ? 'selected' : '' }}>Aguardando</option>
                    <option value="FECHADO" {{ request('status') == 'FECHADO' ? 'selected' : '' }}>Fechados</option>
                </select>
                <select name="categoria" id="filtCategoria" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-sm text-slate-800 dark:text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition cursor-pointer" onchange="this.form.submit()">
                    <option value="">Todas Categorias</option>
                    <option value="BUG" {{ request('categoria') == 'BUG' ? 'selected' : '' }}>Bug</option>
                    <option value="MELHORIA" {{ request('categoria') == 'MELHORIA' ? 'selected' : '' }}>Melhoria</option>
                    <option value="DUVIDA" {{ request('categoria') == 'DUVIDA' ? 'selected' : '' }}>Dúvida</option>
                    <option value="OUTRO" {{ request('categoria') == 'OUTRO' ? 'selected' : '' }}>Outro</option>
                </select>
            </div>
        </div>
    </form>

    {{-- ============================================================
         LISTA DE TICKETS (Tabela)
         ============================================================ --}}
    <div class="mb-4 text-sm text-slate-500 dark:text-slate-400 font-medium">
        {{ $tickets->count() }} ticket(s) encontrado(s)
    </div>

    <div id="listaTickets" class="overflow-x-auto bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700/50">
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ticket</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Título</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoria</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prioridade</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Solicitante</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Abertura</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Anexo</th>
                    @role('ADMIN')
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Ações</th>
                    @endrole
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                @forelse ($tickets as $ticket)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                        {{-- TICKET PROTOCOLO --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-blue-500 dark:text-blue-400">
                                SUP-{{ $ticket->created_at->format('Ymd') }}-{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>

                        {{-- TÍTULO E DESCRIÇÃO --}}
                        <td class="px-4 py-4 min-w-[250px]">
                            <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $ticket->titulo }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate max-w-xs">{{ Str::limit($ticket->descricao, 50) }}</p>
                        </td>

                        {{-- CATEGORIA --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-sm font-medium">
                                @if($ticket->categoria == 'BUG')
                                    <span class="text-red-500 dark:text-red-400"><i class="fas fa-bug"></i> Bug / Erro</span>
                                @elseif($ticket->categoria == 'MELHORIA')
                                    <span class="text-green-500 dark:text-green-400"><i class="fas fa-lightbulb"></i> Melhoria</span>
                                @elseif($ticket->categoria == 'DUVIDA')
                                    <span class="text-blue-500 dark:text-blue-400"><i class="fas fa-question-circle"></i> Dúvida</span>
                                @else
                                    <span class="text-slate-500 dark:text-slate-400"><i class="fas fa-clipboard-list"></i> Outro</span>
                                @endif
                            </div>
                        </td>

                        {{-- PRIORIDADE --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-sm font-medium">
                                @if($ticket->prioridade == 'CRITICA')
                                    <span class="text-red-600 dark:text-red-500"><i class="fas fa-flag"></i> Crítica</span>
                                @elseif($ticket->prioridade == 'ALTA')
                                    <span class="text-orange-500 dark:text-orange-400"><i class="fas fa-flag"></i> Alta</span>
                                @elseif($ticket->prioridade == 'MEDIA')
                                    <span class="text-yellow-500 dark:text-yellow-400"><i class="fas fa-flag"></i> Média</span>
                                @else
                                    <span class="text-blue-500 dark:text-blue-400"><i class="fas fa-flag"></i> Baixa</span>
                                @endif
                            </div>
                        </td>

                        {{-- STATUS --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($ticket->status == 'ABERTO')
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold flex items-center gap-1.5 w-max bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                                    <i class="fas fa-envelope-open"></i> Aberto
                                </span>
                            @elseif($ticket->status == 'EM_ANDAMENTO')
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold flex items-center gap-1.5 w-max bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800/50">
                                    <i class="fas fa-spinner fa-spin-pulse"></i> Em Andamento
                                </span>
                            @elseif($ticket->status == 'AGUARDANDO')
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold flex items-center gap-1.5 w-max bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800/50">
                                    <i class="fas fa-clock"></i> Aguardando
                                </span>
                            @else
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold flex items-center gap-1.5 w-max bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800/50">
                                    <i class="fas fa-check-circle"></i> Fechado
                                </span>
                            @endif
                        </td>

                        {{-- SOLICITANTE --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $ticket->user->name ?? 'Usuário Desconhecido' }}</span>
                            </div>
                        </td>

                        {{-- ABERTURA --}}
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-slate-500 dark:text-slate-400">
                            {{ $ticket->created_at->format('d/m/Y H:i') }}
                        </td>

                        {{-- ANEXO --}}
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            @if($ticket->anexo_path)
                                <a href="{{ route('suporte.anexo', $ticket->id) }}" target="_blank" class="text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300 transition" title="Ver Anexo">
                                    <i class="fas fa-paperclip text-lg"></i>
                                </a>
                            @else
                                <span class="text-slate-400 dark:text-slate-600 font-bold">-</span>
                            @endif
                        </td>

                        {{-- AÇÕES (ADMIN) --}}
                        @role('ADMIN')
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            <form method="POST" action="{{ route('suporte.updateStatus', $ticket) }}">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded px-2 py-1 text-xs text-slate-800 dark:text-white outline-none cursor-pointer hover:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                                    <option value="ABERTO" {{ $ticket->status == 'ABERTO' ? 'selected' : '' }}>Aberto</option>
                                    <option value="EM_ANDAMENTO" {{ $ticket->status == 'EM_ANDAMENTO' ? 'selected' : '' }}>Em Andamento</option>
                                    <option value="AGUARDANDO" {{ $ticket->status == 'AGUARDANDO' ? 'selected' : '' }}>Aguardando</option>
                                    <option value="FECHADO" {{ $ticket->status == 'FECHADO' ? 'selected' : '' }}>Fechado</option>
                                </select>
                            </form>
                        </td>
                        @endrole
                    </tr>
                @empty
                    <tr>
                        @role('ADMIN')
                            <td colspan="9" class="px-4 py-12 text-center">
                        @else
                            <td colspan="8" class="px-4 py-12 text-center">
                        @endrole
                            <i class="fas fa-inbox text-5xl text-slate-300 dark:text-slate-600 mb-4"></i>
                            <p class="text-slate-600 dark:text-slate-300 text-lg font-bold">Nenhum ticket encontrado</p>
                            <p class="text-slate-500 dark:text-slate-500 text-sm mt-1">Ajuste os filtros ou crie um novo ticket.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============================================================
         MODAL NOVO TICKET
         ============================================================ --}}
    <div id="modalSuporte" class="fixed inset-0 z-[9999] hidden">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="fecharModalSuporte()"></div>
        
        <!-- Conteúdo do Modal -->
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg theme-bg-card rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden max-h-[90vh] flex flex-col fade-in" onclick="event.stopPropagation()">
                
                <!-- Header do Modal -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-headset text-white text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Suporte do Sistema</h2>
                            <p class="text-xs text-indigo-200">Registrar ocorrência</p>
                        </div>
                    </div>
                    <button onclick="fecharModalSuporte()" class="text-white/80 hover:text-white transition p-1" title="Fechar modal">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Corpo do Modal (scrollável) -->
                <div class="overflow-y-auto flex-1 px-6 py-5 custom-scrollbar">
                    <form id="formSuporte" onsubmit="enviarTicketSuporte(event)" enctype="multipart/form-data">
                        
                        <!-- Informações do usuário -->
                        <div class="mb-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700/50">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1"><i class="fas fa-user mr-1"></i>Solicitante</p>
                            <p class="font-medium text-sm text-slate-800 dark:text-white">{{ auth()->user()->name }} <span class="text-slate-500 dark:text-slate-400">({{ auth()->user()->email }})</span></p>
                        </div>
                        
                        <!-- Categoria e Prioridade -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                    <i class="fas fa-tag mr-1 text-indigo-400"></i>Categoria <span class="text-red-400">*</span>
                                </label>
                                <select name="categoria" id="suporteCategoria" required class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-1 focus:ring-indigo-500 outline-none transition cursor-pointer">
                                    <option value="BUG">🐛 Bug / Erro</option>
                                    <option value="MELHORIA">💡 Melhoria</option>
                                    <option value="DUVIDA">❓ Dúvida</option>
                                    <option value="OUTRO">📋 Outro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                    <i class="fas fa-flag mr-1 text-indigo-400"></i>Prioridade <span class="text-red-400">*</span>
                                </label>
                                <select name="prioridade" id="suportePrioridade" required class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-1 focus:ring-indigo-500 outline-none transition cursor-pointer">
                                    <option value="BAIXA">Baixa</option>
                                    <option value="MEDIA" selected>Média</option>
                                    <option value="ALTA">Alta</option>
                                    <option value="CRITICA">Crítica</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Título -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                <i class="fas fa-heading mr-1 text-indigo-400"></i>Título <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="titulo" id="suporteTitulo" required maxlength="200" placeholder="Resumo breve da ocorrência" class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-1 focus:ring-indigo-500 outline-none transition">
                        </div>
                        
                        <!-- Descrição -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                <i class="fas fa-align-left mr-1 text-indigo-400"></i>Descrição do Problema <span class="text-red-400">*</span>
                            </label>
                            <textarea name="descricao" id="suporteDescricao" required rows="5" maxlength="5000" placeholder="Descreva detalhadamente o problema encontrado. Inclua passos para reproduzir, se possível." class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-1 focus:ring-indigo-500 outline-none transition resize-y"></textarea>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                <span id="suporteDescricaoCount">0</span>/5000 caracteres
                            </p>
                        </div>
                        
                        <!-- Anexo (foto/print - opcional) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-slate-300">
                                <i class="fas fa-camera mr-1 text-indigo-400"></i>Anexar foto/print <span class="text-xs text-slate-500 dark:text-slate-400">(opcional, máx 5MB)</span>
                            </label>
                            <div id="suporteAnexoArea" class="relative">
                                <input type="file" name="anexo" id="suporteAnexo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" onchange="previewAnexoSuporte(this)">
                                <button type="button" onclick="document.getElementById('suporteAnexo').click()" class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-3 text-sm text-left flex items-center gap-2 hover:border-indigo-500/50 transition cursor-pointer">
                                    <i class="fas fa-paperclip text-slate-500 dark:text-slate-400"></i>
                                    <span id="suporteAnexoLabel" class="text-slate-500 dark:text-slate-400">Clique para selecionar uma imagem...</span>
                                </button>
                                <!-- Preview da imagem -->
                                <div id="suporteAnexoPreview" class="hidden mt-2 relative inline-block">
                                    <img id="suporteAnexoImg" src="#" alt="Preview" class="max-h-32 rounded-lg border border-slate-200 dark:border-slate-700">
                                    <button type="button" onclick="removerAnexoSuporte()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-400 text-white rounded-full text-xs flex items-center justify-center shadow" title="Remover anexo">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                    </form>
                </div>
                
                <!-- Footer do Modal -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between gap-3 flex-shrink-0">
                    <button type="button" onclick="fecharModalSuporte()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium transition">
                        Cancelar
                    </button>
                    <button type="submit" form="formSuporte" id="btnEnviarSuporte" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-lg shadow-indigo-900/20">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
.fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@push('scripts')
<script>
    // ==================== LÓGICA DO MODAL ====================
    function abrirModalNovoTicket() {
        document.getElementById('modalSuporte').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Evita scroll do fundo
    }

    function fecharModalSuporte() {
        document.getElementById('modalSuporte').classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('formSuporte').reset();
        removerAnexoSuporte();
        document.getElementById('suporteDescricaoCount').textContent = '0';
    }

    // Fecha modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalSuporte = document.getElementById('modalSuporte');
            if (modalSuporte && !modalSuporte.classList.contains('hidden')) {
                fecharModalSuporte();
            }
        }
    });

    // Contador de caracteres do textarea
    const suporteDesc = document.getElementById('suporteDescricao');
    if (suporteDesc) {
        suporteDesc.addEventListener('input', function() {
            document.getElementById('suporteDescricaoCount').textContent = this.value.length;
        });
    }

    // Preview do Anexo
    function previewAnexoSuporte(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Validar tamanho (5MB = 5 * 1024 * 1024 bytes)
            if (file.size > 5242880) {
                if(typeof Swal !== 'undefined') Swal.fire('Aviso', 'A imagem deve ter no máximo 5MB.', 'warning');
                else alert('A imagem deve ter no máximo 5MB.');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('suporteAnexoImg').src = e.target.result;
                document.getElementById('suporteAnexoPreview').classList.remove('hidden');
                document.getElementById('suporteAnexoLabel').textContent = file.name;
                document.getElementById('suporteAnexoLabel').classList.add('text-indigo-500', 'dark:text-indigo-400');
            }
            reader.readAsDataURL(file);
        }
    }

    function removerAnexoSuporte() {
        document.getElementById('suporteAnexo').value = '';
        document.getElementById('suporteAnexoImg').src = '#';
        document.getElementById('suporteAnexoPreview').classList.add('hidden');
        document.getElementById('suporteAnexoLabel').textContent = 'Clique para selecionar uma imagem...';
        document.getElementById('suporteAnexoLabel').classList.remove('text-indigo-500', 'dark:text-indigo-400');
    }

    // Submissão do Formulário
    function enviarTicketSuporte(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btnEnviarSuporte');
        const form = document.getElementById('formSuporte');
        const originalContent = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        
        const formData = new FormData(form);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('{{ route("suporte.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            fecharModalSuporte();
            btn.disabled = false;
            btn.innerHTML = originalContent;
            
            if (data.success) {
                if(typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ticket Criado!',
                        text: data.message || 'Seu ticket foi registrado com sucesso.',
                        confirmButtonColor: '#4f46e5',
                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a'
                    }).then(() => {
                        window.location.reload(); // Recarrega para mostrar o novo ticket
                    });
                } else {
                    alert(data.message || 'Ticket criado com sucesso!');
                    window.location.reload();
                }
            } else {
                if(typeof Swal !== 'undefined') {
                    Swal.fire('Erro', 'Ocorreu um erro ao criar o ticket.', 'error');
                } else {
                    alert('Erro ao criar o ticket.');
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            fecharModalSuporte();
            btn.disabled = false;
            btn.innerHTML = originalContent;
            
            if(typeof Swal !== 'undefined') Swal.fire('Erro', 'Erro de comunicação com o servidor.', 'error');
            else alert('Erro de comunicação com o servidor.');
        });
    }
    // ==================== LÓGICA DOS FILTROS E BUSCA ====================

    function filtrarPorStatus(status) {
        document.getElementById('filtStatus').value = status;
        carregarTickets();
    }

    function carregarTickets() {
        console.log('Carregando tickets (lógica AJAX aqui)...');
    }
    
    let debounceTimer;
    function debounceCarregar() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            carregarTickets();
        }, 500);
    }
</script>
@endpush

@endsection
