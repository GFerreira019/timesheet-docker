@extends('layouts.app')

@section('title', 'Dashboard de Conformidade')

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
@endpush

@section('content')

{{-- ============================================================
     BANNER FERIADO
     ============================================================ --}}
@if($nome_feriado)
<div class="max-w-7xl mx-auto px-4 sm:px-6 mb-4">
    <div class="bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 px-4 py-3 rounded-xl flex items-center gap-3 animate-pulse">
        <i class="fas fa-calendar-star text-indigo-400 text-xl"></i>
        <div>
            <p class="font-bold text-sm">FERIADO NESTA DATA</p>
            <p class="text-xs text-indigo-200"><span class="uppercase font-bold text-white">{{ $nome_feriado }}</span>. As metas de horas foram zeradas para este dia.</p>
        </div>
    </div>
</div>
@endif

{{-- ============================================================
     HEADER — Padrão CONNECT (Seção 14.1)
     ============================================================ --}}
<x-page-header 
    title="Dashboard de Conformidade" 
    subtitle="Monitoramento de carga horária diária"
    icon="fas fa-tasks"
    iconBg="from-cyan-500 to-cyan-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-7xl mx-auto w-full px-4 sm:px-6 flex justify-end mb-3 -mt-6"> 
    <x-notificacoes-bell />
</div>

<div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3 max-w-7xl mx-auto w-full px-4 sm:px-6 mb-6">
    
    {{-- Indicador de Status WhatsApp --}}
    <div id="wpp-status-container" title="Verificando WhatsApp..." class="flex items-center gap-2 bg-slate-800 rounded-lg px-3 py-1.5 border border-slate-700 shadow-sm h-[42px] cursor-help transition">
        <i id="wpp-status-icon" class="fab fa-whatsapp text-slate-400 text-lg"></i>
        <span class="text-slate-300 text-sm font-medium hidden sm:inline">API WPP</span>
        <div id="wpp-status-dot" class="w-2.5 h-2.5 rounded-full bg-slate-500 animate-pulse ml-1"></div>
    </div>

    {{-- Navegação de Data --}}
    <div class="flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700 shadow-sm h-[42px]">
        <a href="?data={{ $prev_date }}" class="px-2 py-1.5 hover:bg-slate-700 rounded-md text-slate-400 hover:text-white transition h-full flex items-center">
            <i class="fas fa-chevron-left"></i>
        </a>
        
        <div onclick="abrirModalCalendario_calendarioModal()" class="px-3 py-1 h-full flex flex-col justify-center text-center border-l border-r border-slate-700/50 mx-1 rounded group cursor-pointer hover:bg-slate-700/50 transition-colors">
            <span class="block text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase tracking-wider group-hover:text-indigo-400 transition-colors leading-none mb-0.5">Data Referência</span>
            <div class="flex items-center justify-center gap-1 sm:gap-2 leading-none">
                <span class="text-white font-mono font-bold text-xs sm:text-sm group-hover:text-indigo-300 transition-colors">{{ \Carbon\Carbon::parse($data_ref)->format('d/m/Y') }}</span>
                <i class="fas fa-calendar-alt text-slate-500 group-hover:text-indigo-400 text-xs sm:text-sm transition-colors"></i>
            </div>
        </div>
        
        <a href="?data={{ $next_date }}" class="px-2 py-1.5 hover:bg-slate-700 rounded-md text-slate-400 hover:text-white transition h-full flex items-center">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    <div class="h-8 w-px bg-slate-700/50 mx-1 hidden lg:block"></div>

    {{-- Aviso Manual --}}
    <button type="button" onclick="document.getElementById('modal-aviso-manual').classList.remove('hidden')" class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-3 sm:px-4 rounded-lg flex items-center justify-center gap-2 border border-slate-700 transition-all text-sm h-[42px]">
        <i class="fas fa-envelope text-indigo-400"></i>
        <span class="hidden sm:inline">Enviar Aviso</span>
    </button>

    {{-- Notificar Pendentes --}}
    @if($is_feriado && count($lista_ausente) === 0 && count($lista_incompleto) === 0)
        <button type="button" disabled class="bg-slate-800 border border-slate-700/50 text-slate-500 font-bold px-3 sm:px-4 rounded-lg flex items-center justify-center gap-2 cursor-not-allowed opacity-50 text-sm h-[42px]">
            <i class="fas fa-check-circle"></i>
            <span class="hidden sm:inline">Sem Pendências</span>
        </button>
    @else
        <button type="button" onclick="document.getElementById('modal-notificar').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-3 sm:px-4 rounded-lg flex items-center justify-center gap-2 shadow-lg shadow-indigo-900/20 transition-all text-sm h-[42px]">
            <i class="fas fa-bell animate-pulse"></i>
            <span class="hidden sm:inline">Notificar Pendentes</span>
        </button>
    @endif
</div>

<div class="max-w-7xl mx-auto w-full px-4 sm:px-6">

    {{-- ============================================================
         CARDS DE RESUMO (KPIs)
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 fade-in">
        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700/50">
            <span class="text-xs text-slate-400 font-bold uppercase flex items-center gap-2 mb-1">
                <i class="fas fa-chart-pie text-indigo-400"></i>
                Adesão Total
            </span>
            <div class="text-2xl font-bold text-white">{{ $percentual_adesao }}%</div>
            <div class="w-full bg-slate-900 h-1.5 rounded-full mt-2 overflow-hidden border border-slate-700/50">
                <div class="bg-indigo-500 h-full" style="width: {{ $percentual_adesao }}%"></div>
            </div>
        </div>

        <div class="bg-slate-800 p-4 rounded-xl border border-green-500/30">
            <span class="text-xs text-green-400 font-bold uppercase flex items-center gap-2 mb-1">
                <i class="fas fa-check-circle text-green-400"></i>
                Enviaram Corretamente
            </span>
            <div class="text-2xl font-bold text-white">{{ count($lista_ok) }} <span class="text-sm font-normal text-slate-500">colaboradores</span></div>
        </div>

        <div class="bg-slate-800 p-4 rounded-xl border border-yellow-500/30">
            <span class="text-xs text-yellow-400 font-bold uppercase flex items-center gap-2 mb-1">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                Horas Divergentes
            </span>
            <div class="text-2xl font-bold text-white">{{ count($lista_incompleto) }} <span class="text-sm font-normal text-slate-500">colaboradores</span></div>
        </div>

        <div class="bg-slate-800 p-4 rounded-xl border border-red-500/30">
            <span class="text-xs text-red-400 font-bold uppercase flex items-center gap-2 mb-1">
                <i class="fas fa-times-circle text-red-400"></i>
                Não Enviaram
            </span>
            <div class="text-2xl font-bold text-white">{{ count($lista_ausente) }} <span class="text-sm font-normal text-slate-500">colaboradores</span></div>
        </div>
    </div>

    {{-- ============================================================
         AS TRÊS COLUNAS DE STATUS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 fade-in" style="animation-delay: 100ms">
        
        {{-- COLUNA 1: AUSENTES --}}
        <div class="flex flex-col h-[700px]">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-user-times text-slate-500"></i>
                    Ausentes (0h)
                </h3>
                <span class="bg-slate-800 text-slate-300 text-xs font-bold px-2.5 py-1 rounded-full border border-slate-700/50">
                    {{ count($lista_ausente) }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar">
                @forelse($lista_ausente as $item)
                {{-- Mini Card (DS) --}}
                <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 hover:border-slate-500 transition group">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <p class="font-bold text-white text-sm truncate">{{ $item['nome'] }}</p>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">{{ $item['cargo'] }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex bg-slate-900/50 text-slate-500 font-mono font-bold text-xs px-2 py-1 rounded border border-slate-700/50">00:00</span>
                        </div>
                    </div>
                </div>
                @empty
                    @if($nome_feriado)
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-700 rounded-xl bg-slate-800/30">
                        <i class="fas fa-calendar-day text-3xl text-indigo-400 mb-3"></i>
                        <p class="font-bold text-white text-sm">Dia de Folga</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $nome_feriado }}</p>
                    </div>
                    @else
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-700 rounded-xl bg-slate-800/30">
                        <i class="fas fa-check-circle text-3xl text-slate-600 mb-3"></i>
                        <p class="text-sm text-slate-400">Nenhum colaborador ausente.</p>
                    </div>
                    @endif
                @endforelse
            </div>
        </div>

        {{-- COLUNA 2: INCOMPLETOS --}}
        <div class="flex flex-col h-[700px]">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="text-sm font-bold text-yellow-500 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    Divergências
                </h3>
                <span class="bg-yellow-500/20 text-yellow-500 text-xs font-bold px-2.5 py-1 rounded-full border border-yellow-500/30">
                    {{ count($lista_incompleto) }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar">
                @forelse($lista_incompleto as $item)
                {{-- Mini Card (DS) --}}
                <div class="bg-slate-800 rounded-xl border border-yellow-500/30 p-4 hover:border-yellow-500/50 transition group">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <p class="font-bold text-white text-sm truncate">{{ $item['nome'] }}</p>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">{{ $item['cargo'] ?? 'Colaborador' }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex bg-yellow-500/10 text-yellow-500 font-mono font-bold text-xs px-2 py-1 rounded border border-yellow-500/20">{{ $item['total_str'] }}</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center text-xs mt-3 pt-3 border-t border-slate-700/50">
                        <span class="text-slate-500 flex items-center gap-1"><i class="fas fa-list-ul"></i> {{ $item['qtd_registros'] ?? '0' }} reg.</span>
                        
                        {{-- Badge de Erro/Perigo do .md (Vermelho) --}}
                        <span class="inline-flex items-center gap-1 bg-red-500/10 text-red-400 border border-red-500/20 font-mono font-bold px-2 py-0.5 rounded text-[11px]">
                            <i class="fas fa-clock text-[9px]"></i>
                            Faltam {{ $item['saldo_negativo'] ?? '' }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="h-full flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-700 rounded-xl bg-slate-800/30">
                    <i class="fas fa-thumbs-up text-3xl text-slate-600 mb-3"></i>
                    <p class="text-sm text-slate-400">Nenhum registro incompleto.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- COLUNA 3: ENVIADOS (OK) --}}
        <div class="flex flex-col h-[700px]">
            <div class="flex items-center justify-between mb-4 px-2">
                <h3 class="text-sm font-bold text-green-500 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Enviados
                </h3>
                <span class="bg-green-500/20 text-green-500 text-xs font-bold px-2.5 py-1 rounded-full border border-green-500/30">
                    {{ count($lista_ok) }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar">
                @forelse($lista_ok as $item)
                {{-- Mini Card (DS) --}}
                <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 hover:border-green-500/30 transition group flex justify-between items-center">
                    <div class="min-w-0 pr-2">
                        <p class="font-bold text-white text-sm truncate">{{ $item['nome'] }}</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">
                            <i class="fas fa-list-ul mr-1 text-[9px]"></i> {{ $item['qtd_registros'] ?? '0' }} reg.
                        </p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <span class="inline-flex bg-green-500/10 text-green-400 font-mono font-bold text-xs px-2 py-1 rounded border border-green-500/20">{{ $item['total_str'] }}</span>
                        
                        @if(isset($item['saldo_positivo']) && $item['saldo_positivo'] !== '')
                        <span class="block text-[9px] text-indigo-400 font-mono mt-1 font-bold">
                            <i class="fas fa-plus text-[8px]"></i> {{ $item['saldo_positivo'] }} Extra
                        </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="h-full flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-700 rounded-xl bg-slate-800/30">
                    <i class="fas fa-box-open text-3xl text-slate-600 mb-3"></i>
                    <p class="text-sm text-slate-400">
                        @if($nome_feriado)
                            Nenhum envio (Feriado).
                        @else
                            Nenhum enviado.
                        @endif
                    </p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ============================================================
     MODAIS (NOTIFICAR & AVISO MANUAL)
     Mantendo as estruturas padrão de Modais do DS
     ============================================================ --}}

{{-- Modal: Confirmar Notificação em Massa --}}
<div id="modal-notificar" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-slate-800 border border-slate-700 rounded-xl shadow-2xl overflow-hidden fade-in">
            <div class="px-5 py-4 border-b border-slate-700 bg-slate-900/30 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 class="font-bold text-white text-lg">Notificar Pendentes</h3>
            </div>
            
            <div class="p-5">
                <p class="text-sm text-slate-300 leading-relaxed">
                    Isso enviará notificações para <span class="text-white font-bold bg-slate-700 px-1.5 py-0.5 rounded">{{ count($lista_ausente) + count($lista_incompleto) }}</span> colaboradores com pendência no dia
                    <span class="font-mono font-bold text-indigo-400">{{ \Carbon\Carbon::parse($data_ref)->format('d/m/Y') }}</span>.
                </p>
                <div class="mt-4 flex items-start gap-2 bg-slate-900/50 p-3 rounded-lg border border-slate-700/50">
                    <i class="fab fa-whatsapp text-green-500 mt-0.5"></i>
                    <p class="text-xs text-slate-400">Cada colaborador receberá automaticamente uma mensagem de cobrança via WhatsApp.</p>
                </div>
            </div>
            
            <form method="POST" action="{{ route('conformidade.notificar_pendencias') }}">
                @csrf
                <input type="hidden" name="data_ref" value="{{ $data_ref }}">
                <div class="px-5 py-4 border-t border-slate-700 bg-slate-900/30 flex gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('modal-notificar').classList.add('hidden')"
                            class="px-4 py-2 bg-slate-700 text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-600 transition border border-slate-600">
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-lg shadow-indigo-900/20">
                        <i class="fas fa-paper-plane"></i>
                        Confirmar e Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Aviso Manual Personalizado --}}
<div id="modal-aviso-manual" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-slate-800 border border-slate-700 rounded-xl shadow-2xl overflow-hidden fade-in">
            <div class="px-5 py-4 border-b border-slate-700 bg-slate-900/30 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-700 text-slate-300 flex items-center justify-center">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3 class="font-bold text-white text-lg">Enviar Aviso Personalizado</h3>
            </div>
            
            <form method="POST" action="{{ route('conformidade.enviar_aviso') }}">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Colaborador <span class="text-red-500">*</span></label>
                        @php
                            $opcoesColabs = [];
                            foreach($colaboradores as $c) {
                                $opcoesColabs[$c->id] = $c->nome_completo . ' (' . ($c->cargo ?? 'Sem Cargo') . ')';
                            }
                        @endphp
                        <x-select2 
                            id="select-colaborador-aviso" 
                            name="colaborador_id" 
                            placeholder="Selecione o colaborador..." 
                            :options="$opcoesColabs" 
                            dropdownParent="modal-aviso-manual" 
                            required 
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Título da Mensagem <span class="text-red-500">*</span></label>
                        <input type="text" name="titulo" placeholder="Ex: Ajuste de ponto necessário" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Mensagem <span class="text-red-500">*</span></label>
                        <textarea name="mensagem" rows="4" placeholder="Escreva a mensagem..." required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none resize-none transition"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Data de Referência</label>
                        <input type="date" name="data_referencia" value="{{ $data_ref }}" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2.5 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition text-slate-400">
                    </div>
                </div>
                
                <div class="px-5 py-4 border-t border-slate-700 bg-slate-900/30 flex gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('modal-aviso-manual').classList.add('hidden')"
                            class="px-4 py-2 bg-slate-700 text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-600 transition border border-slate-600">
                        Cancelar
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-lg shadow-indigo-900/20">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Aviso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Falhas de WhatsApp --}}
@if(session('falhas_wpp'))
<div id="modal-falhas-wpp" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col fade-in relative">
        
        <div class="px-5 py-4 border-b border-slate-700 bg-slate-900/30 flex items-center justify-between">
            <h3 class="font-bold text-red-400 text-lg flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i> Falhas no Envio (WhatsApp)
            </h3>
            <button onclick="document.getElementById('modal-falhas-wpp').style.display='none'" class="text-slate-400 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-5 overflow-y-auto flex-1 custom-scrollbar space-y-4">
            <p class="text-sm text-slate-300 mb-4">
                Ocorreram falhas ao tentar notificar os seguintes colaboradores. Você pode copiar as mensagens abaixo para enviar manualmente.
            </p>

            @foreach(session('falhas_wpp') as $falha)
                <div class="bg-slate-900/50 border border-slate-700/50 rounded-lg p-4 relative group">
                    {{-- Botão de Copiar Rápido (Top Left da div) --}}
                    <button onclick="copiarTextoNotificacao(this, '{{ base64_encode($falha['mensagem']) }}')" class="absolute top-3 right-3 text-slate-500 hover:text-indigo-400 transition" title="Copiar Mensagem">
                        <i class="far fa-copy text-lg"></i>
                    </button>
                    
                    <p class="font-bold text-white text-sm pr-8">{{ $falha['nome'] }}</p>
                    <p class="text-xs text-red-400 mt-1 mb-3"><i class="fas fa-info-circle"></i> {{ $falha['erro'] }}</p>
                    
                    <div class="bg-slate-800 p-3 rounded text-sm text-slate-300 whitespace-pre-wrap border border-slate-700 font-mono">{{ $falha['mensagem'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="px-5 py-4 border-t border-slate-700 bg-slate-900/30 flex justify-end">
            <button type="button" onclick="document.getElementById('modal-falhas-wpp').style.display='none'" class="px-4 py-2 bg-slate-700 text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-600 transition border border-slate-600">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
    function copiarTextoNotificacao(btn, base64Text) {
        // Decodifica o texto em base64 (para evitar problemas de quebras de linha e aspas no JS)
        const text = decodeURIComponent(escape(window.atob(base64Text)));
        navigator.clipboard.writeText(text).then(() => {
            const icon = btn.querySelector('i');
            icon.classList.remove('far', 'fa-copy');
            icon.classList.add('fas', 'fa-check', 'text-green-500');
            setTimeout(() => {
                icon.classList.remove('fas', 'fa-check', 'text-green-500');
                icon.classList.add('far', 'fa-copy');
            }, 2000);
        }).catch(err => {
            console.error('Falha ao copiar:', err);
        });
    }
</script>
@endif


@push('styles')
<style>
/* Custom Scrollbar minimalista para as colunas */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
</style>
@endpush

{{-- Instanciando o Componente de Calendário --}}
<x-modal-calendario id="calendarioModal" titulo="Verificar Registros" dataRefStr="{{ $data_ref }}" :mostrar-legenda="true" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusContainer = document.getElementById('wpp-status-container');
    const statusIcon = document.getElementById('wpp-status-icon');
    const statusDot = document.getElementById('wpp-status-dot');
    
    function checkWppStatus() {
        fetch('{{ route("whatsapp.status") }}')
            .then(response => {
                if(!response.ok) throw new Error('Servidor offline');
                return response.json();
            })
            .then(data => {
                const statusRaw = data.status_raw || data.status || '?';
                
                // Reset de classes
                statusDot.className = 'w-2.5 h-2.5 rounded-full ml-1';
                statusIcon.className = 'fab fa-whatsapp text-lg';
                
                if (data.conectado === true) {
                    statusDot.classList.add('bg-green-500');
                    statusIcon.classList.add('text-green-400');
                    statusContainer.setAttribute('title', 'Conectado (' + statusRaw + ')');
                } else if (statusRaw === 'qrReadSuccess' || statusRaw === 'QR_CODE') {
                    statusDot.classList.add('bg-yellow-500', 'animate-pulse');
                    statusIcon.classList.add('text-yellow-400');
                    statusContainer.setAttribute('title', 'Sincronizando / Aguardando QR (' + statusRaw + ')');
                } else {
                    statusDot.classList.add('bg-red-500');
                    statusIcon.classList.add('text-red-400');
                    statusContainer.setAttribute('title', 'Desconectado (' + statusRaw + ')');
                }
            })
            .catch(error => {
                statusDot.className = 'w-2.5 h-2.5 rounded-full bg-red-500 ml-1';
                statusIcon.className = 'fab fa-whatsapp text-red-500 text-lg';
                statusContainer.setAttribute('title', 'Desconectado / Node Offline');
            });
    }

    // Executa imediatamente na inicialização
    checkWppStatus();
    // Continua verificando a cada 30 segundos
    setInterval(checkWppStatus, 30000);
});
</script>

@endsection
