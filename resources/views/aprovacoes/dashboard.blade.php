@extends('layouts.app')

@section('title', 'Central de Aprovações')

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

/* ==========================================================
   Card de Aprovação — Hover suave (Desktop only)
   ========================================================== */

@media (min-width: 768px) {
    .approval-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,.25);
    }
}

.approval-card {
    transition: all 0.3s ease;
}
</style>

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    backUrl="{{ route('painel') }}" 
    icon="fas fa-file-signature" 
    iconColor="text-amber-400" 
    title="{{ $titulo }}" 
    subtitle="Registros aguardando análise">
</x-page-header>
    
<div class="w-full">
    <div class="flex items-center justify-end gap-3 mb-3">
        {{-- Badge de aprovados --}}
        @if($statusFiltro === 'APROVADO')
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold bg-emerald-500/20 text-emerald-500 border border-emerald-500/30">
            <i class="fas fa-check-circle text-[10px] sm:text-xs"></i>
            <span>{{ $totalAprovados }} aprovado(s)</span>
        </span>
        {{-- Badge de rejeitados --}}
        @elseif($statusFiltro === 'REJEITADO')
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold bg-red-500/20 text-red-500 border border-red-500/30">
            <i class="fas fa-times-circle text-[10px] sm:text-xs"></i>
            <span>{{ $totalRecusados }} rejeitado(s)</span>
        </span>
        {{-- Badge de pendentes --}}
        @elseif($statusFiltro === 'EM_ANALISE')
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold bg-yellow-500/20 text-yellow-500 border border-yellow-500/30">
            <i class="fas fa-clock text-[10px] sm:text-xs"></i>
            <span>{{ $pendentes->count() }} pendente(s)</span>
        </span>
        @endif
        <x-notificacoes-bell />
    </div>

    {{-- ============================================================
         FILTROS GLOBAIS (Apenas ADMIN)
         ============================================================ --}}
    @if($nivelAcesso === 'ADMIN')
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 mb-4 md:mb-6">
        <form method="GET" action="{{ route('aprovacoes.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            {{-- Mantém o Status selecionado ao filtrar --}}
            <input type="hidden" name="status" value="{{ request('status', 'EM_ANALISE') }}">

            <div class="lg:col-span-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 ml-1">Data Início</label>
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 outline-none">
            </div>

            <div class="lg:col-span-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 ml-1">Data Fim</label>
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 outline-none">
            </div>

            <div class="lg:col-span-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 ml-1">Colaborador</label>
                <select name="colaborador_id" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                    <option value="">Todos</option>
                    @foreach($colaboradores as $c)
                        <option value="{{ $c->id }}" {{ request('colaborador_id') == $c->id ? 'selected' : '' }}>{{ $c->nome_completo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 ml-1">Obra (Projeto)</label>
                <select name="projeto_id" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                    <option value="">Todas</option>
                    @foreach($projetos as $p)
                        <option value="{{ $p->id }}" {{ request('projeto_id') == $p->id ? 'selected' : '' }}>{{ $p->codigo }} - {{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 ml-1">Setor</label>
                <select name="setor_id" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                    <option value="">Todos</option>
                    @foreach($setores as $s)
                        <option value="{{ $s->id }}" {{ request('setor_id') == $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-1 flex items-end gap-2">
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-2 px-3 rounded-lg text-xs transition shadow-lg flex items-center justify-center gap-2 h-[34px]">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                @if(request('data_inicio') || request('data_fim') || request('colaborador_id') || request('projeto_id') || request('setor_id'))
                    <a href="{{ route('aprovacoes.dashboard', ['status' => request('status', 'EM_ANALISE')]) }}" class="bg-slate-700 hover:bg-slate-600 text-slate-300 px-3 rounded-lg transition h-[34px] flex items-center justify-center" title="Limpar Filtros">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
    @endif

    {{-- ============================================================
         KPIs — Cards de Resumo (grid mobile-first)
         Mobile: 1 coluna | SM: 3 colunas
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 md:mb-6">

        {{-- KPI: Total Pendentes --}}
        <a href="?status=EM_ANALISE" class="bg-slate-800 rounded-xl border border-slate-700/50 p-3 md:p-4 flex items-center gap-3 hover:bg-slate-700 transition cursor-pointer {{ $statusFiltro === 'EM_ANALISE' ? 'ring-2 ring-amber-500' : '' }}">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-amber-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] md:text-xs text-slate-500 uppercase font-bold tracking-wider">Pendentes</p>
                <p class="text-lg md:text-2xl font-bold text-amber-400">{{ $totalPendentes }}</p>
            </div>
        </a>

        {{-- KPI: Aprovados Hoje --}}
        <a href="?status=APROVADO" class="bg-slate-800 rounded-xl border border-slate-700/50 p-3 md:p-4 flex items-center gap-3 hover:bg-slate-700 transition cursor-pointer {{ $statusFiltro === 'APROVADO' ? 'ring-2 ring-emerald-500' : '' }}">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-circle text-emerald-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] md:text-xs text-slate-500 uppercase font-bold tracking-wider">Aprovados</p>
                <p class="text-lg md:text-2xl font-bold text-emerald-400">{{ $totalAprovados }}</p>
            </div>
        </a>

        {{-- KPI: Rejeitados Hoje --}}
        <a href="?status=REJEITADO" class="bg-slate-800 rounded-xl border border-slate-700/50 p-3 md:p-4 flex items-center gap-3 hover:bg-slate-700 transition cursor-pointer {{ $statusFiltro === 'REJEITADO' ? 'ring-2 ring-red-500' : '' }}">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-red-500/20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-times-circle text-red-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] md:text-xs text-slate-500 uppercase font-bold tracking-wider">Rejeitados</p>
                <p class="text-lg md:text-2xl font-bold text-red-400">{{ $totalRecusados }}</p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         ESTADO VAZIO — Nenhum pendente
         ============================================================ --}}
    @if($pendentes->isEmpty())
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 py-12 md:py-20 px-4 text-center">
        <div class="w-16 h-16 md:w-20 md:h-20 mx-auto mb-4 md:mb-6 bg-emerald-500/20 rounded-full flex items-center justify-center">
            <i class="fas fa-check-circle text-emerald-500 text-2xl md:text-3xl"></i>
        </div>
        <h2 class="text-lg md:text-xl font-bold text-emerald-400 mb-2">Tudo em dia!</h2>
        <p class="text-xs md:text-sm text-slate-400">Nenhum registro pendente de aprovação no momento.</p>
    </div>

    @else
    {{-- ============================================================
         LISTA DE CARDS — Substituição da tabela
         Mobile: Cards empilhados com layout flex-col
         Desktop: Layout horizontal com botões à direita
         ============================================================ --}}
    <div class="space-y-3 md:space-y-4">
        @foreach($pendentes as $ap)
        <div class="approval-card rounded-xl border border-slate-700/50 p-3 md:p-5 hover:border-slate-600 transition {{ $ap->status_aprovacao === 'SOLICITACAO_AJUSTE' ? 'bg-yellow-900/10 border-yellow-500/30' : 'bg-slate-800' }}">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between md:gap-4">

                {{-- ========================================
                     ESQUERDA: Avatar + Informações
                     ======================================== --}}
                <div class="flex items-start gap-3 flex-1 min-w-0">

                    {{-- Avatar circular com inicial --}}
                    {{-- Container principal empilhando em coluna --}}
                    <div class="flex flex-col items-center flex-shrink-0 gap-1"> 

                        {{-- Avatar circular com inicial --}}
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-slate-700 border border-slate-600 flex items-center justify-center">
                            <span class="text-sm md:text-lg font-bold text-slate-300">
                                {{ strtoupper(substr($ap->colaborador->nome_completo ?? 'U', 0, 1)) }}
                            </span>
                        </div>

                        <div class="flex justify-center" 
                            title="{{ $ap->tipo_aprovacao === 'automatica' ? 'Aprovação Automática (Sistema)' : ($ap->tipo_aprovacao === 'manual' ? 'Aprovação Manual (Gestor)' : 'Origem não identificada') }}">
                            
                            @if($ap->tipo_aprovacao === 'automatica')
                                <i class="fas fa-gears text-blue-400 w-5 text-center text-sm cursor-help"></i>
                            @elseif($ap->tipo_aprovacao === 'manual')
                                <i class="fas fa-pen-to-square text-blue-400 w-5 text-center text-sm cursor-help"></i>
                            @else
                                <i class=""></i>
                            @endif
                        </div>
                    </div>

                    {{-- Informações do apontamento --}}
                    <div class="flex-1 min-w-0">

                        {{-- Linha 1: Nome + Badge de edição --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-white text-sm truncate max-w-[200px] sm:max-w-none">
                                {{ $ap->colaborador->nome_completo }}
                            </span>

                            @if($ap->contagem_edicao > 0)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-900/40 text-amber-400 border border-amber-700/40">
                                <i class="fas fa-pen text-[8px]"></i>
                                {{ $ap->contagem_edicao }}x
                            </span>
                            @endif

                            @if($ap->status_aprovacao === 'SOLICITACAO_AJUSTE')
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-500 border border-yellow-500/30">
                                <i class="fas fa-exclamation-circle text-[8px]"></i>
                                Aguardando Ajuste
                            </span>
                            @endif
                        </div>

                        {{-- Linha 2: Data + Horários + Duração --}}
                        <div class="flex items-center gap-2 mt-1 text-xs md:text-sm text-slate-400 flex-wrap">
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-calendar-alt text-slate-500 text-[10px] md:text-xs"></i>
                                {{ \Carbon\Carbon::parse($ap->data_apontamento)->format('d/m/Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono">
                                <i class="fas fa-clock text-slate-500 text-[10px] md:text-xs"></i>
                                <span class="text-emerald-400">{{ substr($ap->hora_inicio, 0, 5) }}</span>
                                <span class="text-slate-600">→</span>
                                <span class="text-red-400">{{ $ap->hora_termino ? substr($ap->hora_termino, 0, 5) : '??:??' }}</span>
                            </span>
                            @if($ap->hora_inicio && $ap->hora_termino)
                            @php
                                $inicio = \Carbon\Carbon::parse($ap->hora_inicio);
                                $termino = \Carbon\Carbon::parse($ap->hora_termino);
                                $diffMin = $inicio->diffInMinutes($termino);
                                $horas = intdiv($diffMin, 60);
                                $minutos = $diffMin % 60;
                            @endphp
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-700/60 text-[10px] md:text-xs font-mono text-slate-300">
                                [{{ str_pad($horas, 2, '0', STR_PAD_LEFT) }}:{{ str_pad($minutos, 2, '0', STR_PAD_LEFT) }}]
                            </span>
                            @endif
                        </div>

                        {{-- Linha 3: Obra / Local --}}
                        <div class="mt-1 text-xs md:text-sm">
                            <span class="text-blue-400 font-medium">Obra:</span>
                            <span class="text-slate-400">
                                @php
                                $nomeObra = null;
                                $codigoObra = null;

                                // Tenta pegar do projeto primeiro
                                if ($ap->projeto) {
                                    $nomeObra = $ap->projeto->nome;
                                    $codigoObra = $ap->projeto->codigo;
                                } 
                                // Se não tiver, pega do cliente
                                elseif ($ap->codigoCliente) {
                                    $nomeObra = $ap->codigoCliente->nome;
                                    $codigoObra = $ap->codigoCliente->codigo;
                                } 
                                // Senão, pega do centro de custo
                                elseif ($ap->centroCusto) {
                                    $nomeObra = $ap->centroCusto->nome;
                                    $codigoObra = $ap->centroCusto->codigo;
                                }
                                @endphp

                                {{ $nomeObra ?? '' }}
                                @if($nomeObra && $codigoObra)
                                    -
                                @endif
                                {{ $codigoObra ?? '' }}
                            </span>
                        </div>

                    </div>

                </div>

                {{-- ========================================
                     DIREITA: Botão de Ação
                     Mobile: Botão full-width com h-11 (touch target)
                     Desktop: Botão inline à direita
                     ======================================== --}}
                <div class="flex-shrink-0 md:ml-4">
                    @if($ap->contagem_edicao > 0)
                    {{-- Botão Warning/Atenção (amarelo) — Seção 2.4: bg-yellow-500 --}}
                    <a href="{{ route('aprovacoes.analise', $ap->id) }}"
                       class="flex items-center justify-center gap-2 w-full md:w-auto px-4 py-3 md:py-2.5 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 active:bg-yellow-700 transition h-11 md:h-auto">
                        <i class="fas fa-pen"></i>
                        Analisar Alteração
                    </a>
                    @else
                    {{-- Botão Primário (roxo) — Seção 2.4: bg-purple-600 --}}
                    <a href="{{ route('aprovacoes.analise', $ap->id) }}"
                       class="flex items-center justify-center gap-2 w-full md:w-auto px-4 py-3 md:py-2.5 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 active:bg-purple-800 transition h-11 md:h-auto">
                        <i class="fas fa-eye"></i>
                        Avaliar Registro
                    </a>
                    @endif
                </div>

            </div>
        </div>
        @endforeach
    </div>

    {{-- Paginação --}}
    <div class="mt-5 mb-2">
        {{ $pendentes->appends(request()->query())->links() }}
    </div>
    @endif

    {{-- ============================================================
         14.4 BOTÃO VOLTAR INFERIOR
         Mobile: padding reduzido | Touch target confortável
         ============================================================ --}}
    <div class="text-center mt-6 mb-6 md:mt-8 md:mb-8">
        <a href="{{ route('painel') }}"
           class="inline-flex items-center gap-2 px-5 py-3 bg-slate-700 hover:bg-slate-600 active:bg-slate-500 rounded-lg font-medium transition text-sm h-11">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Painel
        </a>
    </div>

</div>

@endsection
