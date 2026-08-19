@extends('layouts.app')

@section('title', 'Trilha de Auditoria')

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

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
}
</style>
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

{{-- ============================================================
     HEADER — Padrão CONNECT (Seção 14.1)
     ============================================================ --}}
<x-page-header 
    title="Trilha de Auditoria" 
    subtitle="Rastreabilidade completa de ações e segurança do sistema"
    icon="fas fa-history"
    iconBg="from-indigo-500 to-indigo-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

@if(request('filtro') !== 'notificacoes')
    {{-- ============================================================
         Filtros Padrão (Logs de Auditoria)
         ============================================================ --}}
    <div class="flex items-center justify-end gap-2 max-w-7xl mx-auto px-4 sm:px-6 mb-2">
        <div class="w-full md:justify-start md:flex items-center gap-2">
            @if($filtro_user)
                @foreach($usuarios as $u)
                    @if($u->id == $filtro_user)
                    <div class="flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold fade-in">
                        <span class="hidden sm:inline md:uppercase text-[9px] text-indigo-400/70">Usuário:</span>
                        {{ $u->name ?? $u->email }}
                    </div>
                    @endif
                @endforeach
            @endif

            @if($filtro_acao)
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-teal-500/20 border border-teal-500/30 text-teal-300 text-xs font-bold fade-in">
                <span class="uppercase text-[9px] text-teal-400/70">Ação:</span>
                {{ $filtro_acao }}
            </div>
            @endif
        </div>
            
        <button onclick="document.getElementById('modalCalendarioAuditoria').classList.remove('hidden')" class="flex items-center gap-2 px-3 sm:px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-300 hover:text-white hover:border-indigo-500 transition h-[42px]">
            <i class="fas fa-calendar-alt text-indigo-400"></i>
            <span class="hidden sm:inline">{{ request('data') ? \Carbon\Carbon::parse(request('data'))->format('d/m/Y') : 'Filtrar Data' }}</span>
        </button>
        @if(request('data'))
        <a href="{{ route('owner.auditoria') }}" class="flex items-center justify-center w-[42px] h-[42px] bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-red-400 hover:border-red-500/50 transition" title="Limpar Filtro de Data">
            <i class="fas fa-times"></i>
        </a>
        @endif
        
        <button type="button" onclick="abrirModalFiltros()" class="group relative bg-slate-800 hover:bg-slate-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-bold transition-all border border-slate-700 hover:border-slate-600 h-[42px] flex items-center gap-2 shadow-sm">
            <i class="fas fa-filter text-indigo-400 group-hover:text-white transition-colors"></i>
            <span class="hidden sm:inline">Filtrar</span>
            @if($filtro_user || $filtro_acao)
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
            </span>
            @endif
        </button>

        @if($filtro_user || $filtro_acao)
        <a href="?data_ini={{ $filtro_data }}" class="bg-rose-900/20 hover:bg-rose-900/40 text-rose-400 hover:text-white p-2.5 rounded-lg border border-rose-900/30 hover:border-rose-500/50 transition-all h-[42px] w-[42px] flex items-center justify-center" title="Limpar Filtros">
            <i class="fas fa-times"></i>
        </a>
        @endif
    </div>
@elseif(request('filtro') === 'notificacoes')
    {{-- ============================================================
         Filtros Exclusivos (Notificações)
         ============================================================ --}}
    <div class="flex items-center justify-end gap-2 max-w-7xl mx-auto px-4 sm:px-6 mb-2 fade-in">
        <form id="form-filtro-notificacoes" method="GET" class="w-full flex flex-col sm:flex-row items-center justify-end gap-2">
            {{-- Preserva a aba ativa --}}
            <input type="hidden" name="filtro" value="notificacoes">
            
            {{-- Filtros por Destinatário e Data --}}
            <div class="w-full md:justify-start md:flex items-center gap-2">
                {{-- Busca por Destinatário --}}
                <div class="relative w-full sm:w-auto flex-1 max-w-sm text-left">
                    @php
                        $opcoesColabs = [];
                        foreach($colaboradores ?? [] as $c) {
                            $opcoesColabs[$c->id] = $c->nome_completo . ' (' . ($c->cargo ?? 'Sem Cargo') . ')';
                        }
                    @endphp
                    <x-select2 
                        id="select-colaborador-auditoria" 
                        name="colaborador_id" 
                        placeholder="Filtrar por destinatário..." 
                        :options="$opcoesColabs" 
                        :selected="request('colaborador_id')"
                    />
                </div>

                {{-- Filtro de Data --}}
                <div class="relative w-full sm:w-auto">
                    {{-- O "color-scheme: dark" no CSS força o ícone de calendário do Chrome a ficar claro --}}
                    <input type="date" name="data" value="{{ request('data') }}" 
                        onchange="this.form.submit()"
                        style="color-scheme: dark;"
                        class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm text-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all h-[42px] shadow-sm cursor-pointer">
                </div>
            </div>
            
            {{-- Botão de Limpar (Remover Filtro) padronizado --}}
            @if(request('colaborador_id') || request('data'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'colaborador_id' => null, 'data' => null]) }}" 
                   class="bg-rose-900/20 hover:bg-rose-900/40 text-rose-400 hover:text-white p-2.5 rounded-lg border border-rose-900/30 hover:border-rose-500/50 transition-all h-[42px] w-[42px] flex items-center justify-center mt-2 sm:mt-0 shadow-sm shrink-0" title="Limpar Filtros">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </form>
    </div>
@endif

<div class="max-w-7xl mx-auto w-full px-4 sm:px-6">

    {{-- Modal Filtros --}}
    <div id="modal-filtros" class="relative z-50 hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                    <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fas fa-filter text-indigo-400"></i>
                            Filtrar Auditoria
                        </h3>
                        <button type="button" onclick="fecharModalFiltros()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                    </div>
                    <form method="GET" class="p-6 space-y-5">
                        @if($filtro_data)
                        <input type="hidden" name="data_ini" value="{{ $filtro_data }}">
                        @endif
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Usuário</label>
                            <div class="relative">
                                <select name="user" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="">-- Todos os Usuários --</option>
                                    @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}" @if($filtro_user == $u->id) selected @endif>{{ $u->name ?? $u->email }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Ação</label>
                            <div class="relative">
                                <select name="acao" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="">-- Todas as Ações --</option>
                                    @foreach($acoes as $key => $label)
                                    <option value="{{ $key }}" @if($filtro_acao == $key) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 flex justify-end gap-3 border-t border-slate-800 mt-2">
                            <button type="button" onclick="fecharModalFiltros()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all text-sm flex items-center gap-2">
                                <i class="fas fa-check"></i>
                                Aplicar Filtros
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Cards Topo — Resumo (Base Card DS + Cores de Alerta)
         ============================================================ --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 fade-in">
        {{-- Total Exibido --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700/50 shadow-sm">
            <span class="text-xs text-slate-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-list text-slate-400"></i>
                Total Exibido
            </span>
            <div class="text-2xl font-bold text-white mt-1">{{ count($logs) }} <span class="text-sm font-normal text-slate-500">registros</span></div>
        </div>
        
        {{-- Notificações (Info) --}}
        <a href="{{ request('filtro') === 'notificacoes' ? request()->fullUrlWithQuery(['filtro' => null]) : request()->fullUrlWithQuery(['filtro' => 'notificacoes']) }}" 
           class="bg-slate-800 p-4 rounded-xl hover:border-blue-400 transition-colors cursor-pointer block {{ request('filtro') === 'notificacoes' ? 'border-2 border-blue-500 bg-blue-500/10' : 'border border-blue-500/30' }}">
            <span class="text-xs text-blue-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-bell text-blue-400"></i>
                Notificações
            </span>
            <div class="text-2xl font-bold text-white mt-1">{{ $totalNotificacoes ?? 0 }} Avisos</div>
        </a>

        {{-- Edições (Warning) --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-yellow-500/30">
            <span class="text-xs text-yellow-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-pen text-yellow-400"></i>
                Edições
            </span>
            <div class="text-2xl font-bold text-white mt-1">Críticos</div>
        </div>

        {{-- Exclusões (Danger) --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-red-500/30">
            <span class="text-xs text-red-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-trash text-red-400"></i>
                Exclusões
            </span>
            <div class="text-2xl font-bold text-white mt-1">Atenção</div>
        </div>
    </div>

    {{-- ============================================================
         Timeline
         ============================================================ --}}
    @if(request('filtro') === 'notificacoes')
        <div class="relative border-l-2 border-slate-700/50 ml-4 space-y-8 pb-12 mb-12">
            @forelse($logs as $index => $log)
            {{-- Card de Notificação na Linha do Tempo --}}
            <div class="relative pl-8 group fade-in" style="animation-delay: {{ ($index + 1) * 100 }}ms">
                
                {{-- Bolinha Conectora (Status de Leitura) --}}
                <div class="absolute -left-[9px] top-5 bg-slate-900 rounded-full p-1 border-2 z-10
                    @if($log->lida) border-green-500 text-green-500
                    @else border-slate-500 text-slate-500 @endif">
                    
                    @if($log->lida)
                        <i class="fas fa-envelope-open-text text-[10px]" title="Lida"></i>
                    @else
                        <i class="fas fa-envelope text-[10px]" title="Não lida"></i>
                    @endif
                </div>

                {{-- Card da Linha do Tempo (Padrão DS) --}}
                <div class="bg-slate-800 border border-slate-700/50 rounded-xl hover:border-slate-600 transition-all p-0 overflow-hidden group-hover:shadow-lg">
                    
                    {{-- Header do Card (Tipo + Título + Data/Hora) --}}
                    <div class="px-5 py-3 border-b border-slate-700/50 flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-slate-900/20">
                        <div class="flex items-center gap-3 flex-wrap">
                            {{-- Badge de Tipo --}}
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider
                                @if($log->tipo == 'ALERTA') bg-yellow-500/20 text-yellow-500 border border-yellow-500/30
                                @elseif($log->tipo == 'SUCESSO') bg-green-500/20 text-green-500 border border-green-500/30
                                @else bg-blue-500/20 text-blue-400 border border-blue-500/30 @endif">
                                {{ $log->tipo }}
                            </span>
                            
                            {{-- Título da Notificação --}}
                            <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                                <i class="fas fa-cube text-slate-500 text-[10px]"></i>
                                {{ $log->titulo }}
                            </span>
                        </div>
                        
                        {{-- Lado Direito: Data/Hora --}}
                        <div class="text-right flex items-center gap-3">
                            <p class="text-xs text-slate-400 font-mono font-bold">
                                {{ \Carbon\Carbon::parse($log->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y') }} 
                                <span class="text-slate-600 mx-1">|</span> 
                                {{ \Carbon\Carbon::parse($log->created_at)->timezone('America/Sao_Paulo')->format('H:i:s') }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- Corpo do Card (Destinatário + Mensagem) --}}
                    <div class="p-5 flex items-start gap-4">
                        {{-- Avatar --}}
                        <div class="flex-shrink-0 mt-1">
                            @if($log->colaborador)
                                <div class="h-10 w-10 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300 border border-slate-600">
                                    {{ strtoupper(substr($log->colaborador->nome_completo ?? 'XX', 0, 2)) }}
                                </div>
                            @else
                                <div class="h-10 w-10 rounded-full bg-slate-900 flex items-center justify-center text-slate-600 border border-slate-800 border-dashed">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Dados e Mensagem --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white mb-2">
                                @if($log->colaborador)
                                    {{ $log->colaborador->nome_completo }}
                                @else
                                    <span class="text-slate-500 italic">Destinatário Removido</span>
                                @endif
                            </p>
                            
                            {{-- Detalhes da Notificação (Estilo Console) --}}
                            <div class="bg-slate-950 rounded-lg p-3 border border-slate-700/50">
                                <p class="text-[13px] text-slate-300 leading-relaxed font-mono whitespace-pre-wrap">{!! nl2br(e($log->mensagem)) !!}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            @empty
                <div class="pl-8 py-12 flex flex-col items-center justify-center text-slate-500 border border-dashed border-slate-700 rounded-xl bg-slate-800/30 mb-12">
                    <i class="fas fa-search text-3xl mb-4 text-slate-600 block"></i>
                    <p class="text-lg font-medium text-slate-400">Nenhuma notificação encontrada</p>
                    <p class="text-sm text-slate-500 mt-1">Tente ajustar os filtros de data ou usuário.</p>
                </div>
            @endforelse
        </div>
    @else
    <div class="relative border-l-2 border-slate-700/50 ml-4 space-y-8 pb-12">
        @forelse($logs as $index => $log)
        {{-- Card de Auditoria Normal --}}
        <div class="relative pl-8 group fade-in" style="animation-delay: {{ ($index + 1) * 100 }}ms">
            
            {{-- Bolinha Conectora --}}
            <div class="absolute -left-[9px] top-5 bg-slate-900 rounded-full p-1 border-2 z-10
                @if($log->acao == 'LOGIN') border-blue-500 text-blue-500
                @elseif($log->acao == 'CRIACAO') border-green-500 text-green-500
                @elseif(in_array($log->acao, ['APROVACAO', 'APROVACAO_AJUSTE'])) border-teal-400 text-teal-400
                @elseif(in_array($log->acao, ['REJEICAO', 'EXCLUSAO'])) border-red-500 text-red-500
                @elseif($log->acao == 'EDICAO') border-yellow-500 text-yellow-500
                @elseif($log->acao == 'SOLICITACAO') border-indigo-400 text-indigo-400
                @else border-slate-500 text-slate-500 @endif">
                
                @if($log->acao == 'LOGIN')
                    <i class="fas fa-sign-in-alt text-[10px]"></i>
                @elseif($log->acao == 'CRIACAO')
                    <i class="fas fa-plus text-[10px]"></i>
                @elseif(in_array($log->acao, ['APROVACAO', 'APROVACAO_AJUSTE']))
                    <i class="fas fa-check text-[10px]"></i>
                @elseif(in_array($log->acao, ['REJEICAO', 'EXCLUSAO']))
                    <i class="fas fa-times text-[10px]"></i>
                @elseif($log->acao == 'RESPOSTA')
                    <i class="fas fa-reply text-[10px]"></i>
                @elseif($log->acao == 'EXCLUSAO')
                    <i class="fas fa-trash text-[10px]"></i>
                @elseif($log->acao == 'SOLICITACAO')
                    <i class="fas fa-exclamation text-[10px]"></i>
                @elseif($log->acao == 'EDICAO')
                    <i class="fas fa-pen text-[10px]"></i>
                @else
                    <i class="fas fa-history text-[10px]"></i>
                @endif
            </div>

            {{-- Card da Linha do Tempo (Padrão DS) --}}
            <div class="bg-slate-800 border border-slate-700/50 rounded-xl hover:border-slate-600 transition-all p-0 overflow-hidden group-hover:shadow-lg">
                
                {{-- Header do Card (Ação + Data/Hora) --}}
                <div class="px-5 py-3 border-b border-slate-700/50 flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-slate-900/20">
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- Badge de Ação --}}
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider
                            @if($log->acao == 'LOGIN') bg-blue-500/20 text-blue-500 border border-blue-500/30
                            @elseif(in_array($log->acao, ['REJEICAO', 'EXCLUSAO'])) bg-red-500/20 text-red-500 border border-red-500/30
                            @elseif(in_array($log->acao, ['APROVACAO', 'APROVACAO_AJUSTE', 'RESPOSTA'])) bg-green-500/20 text-green-500 border border-green-500/30
                            @elseif($log->acao == 'CRIACAO') bg-green-500/20 text-green-500 border border-green-500/30
                            @elseif($log->acao == 'SOLICITACAO') bg-indigo-500/20 text-indigo-400 border border-indigo-500/30
                            @elseif($log->acao == 'EDICAO') bg-yellow-500/20 text-yellow-500 border border-yellow-500/30
                            @else bg-slate-500/20 text-slate-400 border border-slate-500/30 @endif">
                            {{ $log->acao }}
                        </span>
                        
                        {{-- Modelo Afetado --}}
                        <span class="text-xs text-slate-400 font-mono flex items-center gap-1">
                            <i class="fas fa-cube text-slate-500 text-[10px]"></i>
                            {{ $log->modelo_afetado }} 
                            @if($log->objeto_id) <span class="text-slate-500">#{{ $log->objeto_id }}</span> @endif
                        </span>
                    </div>
                    
                    {{-- Lado Direito: IP + Data/Hora --}}
                    <div class="text-right flex items-center gap-3">
                        @if($log->ip_address)
                        <div class="hidden sm:flex items-center gap-1.5 text-[10px] text-slate-400 font-mono bg-slate-950 px-2 py-1 rounded border border-slate-700/50">
                            <i class="fas fa-network-wired text-slate-500"></i>
                            {{ $log->ip_address }}
                        </div>
                        @endif
                        <p class="text-xs text-slate-400 font-mono font-bold">
                            {{ \Carbon\Carbon::parse($log->data_hora)->timezone('America/Sao_Paulo')->format('d/m/Y') }} 
                            <span class="text-slate-600 mx-1">|</span> 
                            {{ \Carbon\Carbon::parse($log->data_hora)->timezone('America/Sao_Paulo')->format('H:i:s') }}
                        </p>
                    </div>
                </div>
                
                {{-- Corpo do Card (Usuário + Detalhes) --}}
                <div class="p-5 flex items-start gap-4">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0 mt-1">
                        @if($log->user)
                            <div class="h-10 w-10 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300 border border-slate-600">
                                {{ strtoupper(substr($log->user->name ?? $log->user->email ?? 'XX', 0, 2)) }}
                            </div>
                        @else
                            <div class="h-10 w-10 rounded-full bg-slate-900 flex items-center justify-center text-slate-600 border border-slate-800 border-dashed">
                                <i class="fas fa-user-slash"></i>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Dados e Log Text --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white mb-2">
                            @if($log->user)
                                {{ $log->user->name ?? $log->user->email }}
                            @else
                                <span class="text-slate-500 italic">Sistema / Usuário Removido</span>
                            @endif
                        </p>
                        
                        {{-- Detalhes (Estilo Console) --}}
                        <div class="bg-slate-950 rounded-lg p-3 border border-slate-700/50">
                            @php
                                $isJson = false;
                                $payload = null;
                                if(str_starts_with(trim($log->detalhes), '{')) {
                                    $decoded = json_decode($log->detalhes, true);
                                    if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $isJson = true;
                                        $payload = $decoded;
                                    }
                                }
                            @endphp

                            @if($isJson)
                                <div class="mb-2 flex flex-wrap gap-2 items-center">
                                    @if(isset($payload['apontado']))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-800 text-slate-300 text-xs border border-slate-600">
                                        <i class="fas fa-user-tag text-slate-400"></i>
                                        Apontado: <strong class="text-white">{{ $payload['apontado'] }}</strong>
                                    </span>
                                    @endif
                                    
                                    @if(isset($payload['apontamento_id']) || isset($payload['data_apontamento']))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-900/40 text-indigo-300 text-xs border border-indigo-500/30">
                                        <i class="fas fa-file-invoice text-indigo-400"></i>
                                        Apontamento
                                        @if(isset($payload['apontamento_id']))
                                            <strong class="text-white ml-1">#{{ $payload['apontamento_id'] }}</strong>
                                        @endif
                                        @if(isset($payload['data_apontamento']))
                                            <span class="text-indigo-400/70 ml-1">({{ \Carbon\Carbon::parse($payload['data_apontamento'])->format('d/m/Y') }})</span>
                                        @endif
                                    </span>
                                    @endif
                                </div>
                                <p class="text-[13px] text-slate-300 leading-relaxed font-mono whitespace-pre-wrap">{!! nl2br(e($payload['texto'] ?? '')) !!}</p>
                            @else
                                {{-- Legacy String Fallback --}}
                                @if(preg_match('/\[Apontado: (.*?)\]\n(.*)/s', $log->detalhes, $matches))
                                    <div class="mb-2">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-800 text-slate-300 text-xs border border-slate-600">
                                            <i class="fas fa-user-tag text-slate-400"></i>
                                            Apontado: <strong class="text-white">{{ $matches[1] }}</strong>
                                        </span>
                                    </div>
                                    <p class="text-[13px] text-slate-300 leading-relaxed font-mono whitespace-pre-wrap">{!! nl2br(e(trim($matches[2]))) !!}</p>
                                @else
                                    <p class="text-[13px] text-slate-300 leading-relaxed font-mono whitespace-pre-wrap">{!! nl2br(e($log->detalhes)) !!}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
            @empty
            <div class="pl-8 py-12 flex flex-col items-center justify-center text-slate-500 border border-dashed border-slate-700 rounded-xl bg-slate-800/30">
                <i class="fas fa-search text-3xl mb-4 text-slate-600"></i>
                <p class="text-lg font-medium text-slate-400">Nenhum registro encontrado</p>
                <p class="text-sm text-slate-500 mt-1">Tente ajustar os filtros de data ou usuário.</p>
            </div>
        @endforelse
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Lógica do Modal de Filtros
    const modalFiltros = document.getElementById('modal-filtros');

    function abrirModalFiltros() {
        modalFiltros.classList.remove('hidden');
    }

    function fecharModalFiltros() {
        modalFiltros.classList.add('hidden');
    }

    modalFiltros.addEventListener('click', function(e) {
        if (e.target === this.firstElementChild.nextElementSibling) {
            fecharModalFiltros();
        }
    });

    $(document).ready(function() {
        // Dispara o submit automaticamente quando o usuário seleciona ou limpa um colaborador no Select2
        $('#select-colaborador-auditoria').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script>
@endpush

<x-modal-calendario 
    id="modalCalendarioAuditoria" 
    titulo="Filtrar Logs por Data" 
    rotaFiltro="{{ route('owner.auditoria') }}" 
/>

@endsection
