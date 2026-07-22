@extends('layouts.app')

@section('title', 'Análise de Registro #' . $apontamento->id)

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
    title="Análise de Registro" 
    subtitle="Avaliação detalhada do apontamento"
    icon="fas fa-search"
    iconBg="from-blue-500 to-blue-700"
    backUrl="{{ route('aprovacoes.dashboard') }}">
</x-page-header>

<div class="flex justify-end gap-2 sm:gap-3 pb-2">
    @if($apontamento->status_aprovacao === 'EM_ANALISE')
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold bg-yellow-500/20 text-yellow-500 border border-yellow-500/30">
        <i class="fas fa-circle text-[8px]"></i>
        EM ANÁLISE
    </span>
    @elseif($apontamento->status_aprovacao === 'APROVADO')
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold bg-emerald-500/20 text-emerald-500 border border-emerald-500/30">
        <i class="fas fa-check-circle text-[8px]"></i>
        APROVADO
    </span>
    @else
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-bold bg-red-500/20 text-red-500 border border-red-500/30">
        <i class="fas fa-times-circle text-[8px]"></i>
        REJEITADO
    </span>
    @endif
</div>

<div class="w-full space-y-4 md:space-y-6">

    {{-- ============================================================
         BLOCO 1: AUDITORIA — Alterações Identificadas
         Card com destaque warning (amarelo) se houver diffs
         Mobile-first: Diffs empilhados verticalmente
         ============================================================ --}}
    @if($tem_alteracao)
    <div class="bg-slate-800 rounded-xl border border-yellow-500/30 p-4 md:p-6">
        {{-- Título do card com ícone de warning --}}
        <div class="flex items-center gap-3 mb-4 pb-3 md:pb-4 border-b border-slate-700/50">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-yellow-500/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-base md:text-lg font-bold text-white leading-tight">Auditoria: Alterações Identificadas</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">
                    {{ count($diffs) }} campo(s) modificado(s)
                    @if($historico)
                    — Edição {{ $historico->numero_edicao }} por
                    <span class="text-slate-300">{{ $usuario_editor?->name ?? 'Sistema' }}</span>
                    em <span class="font-mono font-bold text-gray-400">{{ $historico->data_edicao ? \Carbon\Carbon::parse($historico->data_edicao)->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i') : 'Data Indisponível' }}</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Diffs: ANTES → DEPOIS --}}
        <div class="space-y-3">
            @foreach($diffs as $diff)
            <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50">
                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3">
                    <i class="fas fa-{{ $diff['icon'] ?? 'edit' }} mr-1 text-yellow-500"></i>
                    {{ $diff['campo'] }}
                </p>
                
                {{-- Flex col no mobile, row no desktop --}}
                <div class="flex flex-col md:flex-row md:items-stretch gap-2 md:gap-3">
                    {{-- Box ANTES (vermelho) --}}
                    <div class="flex-1 bg-red-900/20 border border-red-700/30 rounded-lg p-2 md:p-3">
                        <span class="block text-[10px] md:text-xs font-bold text-red-500 uppercase mb-0.5 md:mb-1">Antes</span>
                        <span class="text-xs md:text-sm text-red-300 break-words">{{ $diff['antes'] ?: '—' }}</span>
                    </div>

                    {{-- Seta central: arrow-down no mobile, arrow-right no desktop --}}
                    <div class="flex items-center justify-center flex-shrink-0 py-1 md:py-0">
                        <i class="fas fa-arrow-down md:hidden text-slate-600 text-sm"></i>
                        <i class="fas fa-arrow-right hidden md:block text-slate-600"></i>
                    </div>

                    {{-- Box DEPOIS (verde) --}}
                    <div class="flex-1 bg-emerald-900/20 border border-emerald-700/30 rounded-lg p-2 md:p-3">
                        <span class="block text-[10px] md:text-xs font-bold text-emerald-500 uppercase mb-0.5 md:mb-1">Depois</span>
                        <span class="text-xs md:text-sm text-emerald-300 break-words">{{ $diff['depois'] ?: '—' }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @elseif($historico)
    {{-- Editado mas sem diffs detectáveis --}}
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-blue-500/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-sm md:text-base font-bold text-white leading-tight">Registro editado, sem diferenças detectáveis</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">
                    Edição {{ $historico->numero_edicao }} por
                    <span class="text-slate-300">{{ $usuario_editor?->name ?? 'Sistema' }}</span>
                    em {{ $historico->created_at?->format('d/m/Y H:i') }}
                </p>
            </div>
        </div>
    </div>

    @else
    {{-- Registro original --}}
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 md:p-6">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-emerald-500/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-circle text-emerald-500 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-sm md:text-base font-bold text-white leading-tight">Registro Original</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">Este é o registro original, sem histórico de edições.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================
         BLOCO 2: FICHA TÉCNICA DO REGISTRO
         Card padrão: bg-slate-800 rounded-xl border border-slate-700/50
         Mobile-first: p-4, grids 1-column
         ============================================================ --}}
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 md:p-6">

        {{-- Título do card --}}
        <div class="flex items-center gap-3 mb-4 md:mb-6 pb-3 md:pb-4 border-b border-slate-700/50">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-slate-500/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-blue-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-base md:text-lg font-bold text-white leading-tight">Ficha Técnica do Registro</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">Dados completos do apontamento</p>
            </div>
        </div>

        {{-- Grid: 1 col (mobile), 2 cols (sm), 3 cols (md) --}}
        {{-- Grid: 1 col (mobile), 2 cols (sm), 4 cols (md) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-3 md:mb-6">

            {{-- Colaborador (Ocupa 2/4 da grade no desktop e linha inteira no tablet) --}}
            <div class="sm:col-span-2 md:col-span-2 bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50">
                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3">Colaborador</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-slate-700 border border-slate-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-base md:text-lg font-bold text-slate-300">
                            {{ strtoupper(substr($apontamento->colaborador->nome_completo ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-white text-xs md:text-sm truncate">{{ $apontamento->colaborador->nome_completo }}</p>
                        <p class="text-[10px] md:text-xs text-slate-400 truncate">{{ $apontamento->colaborador->cargo ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Data (Ocupa 1/4 da grade) --}}
            <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50">
                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3">Data</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-slate-700 border border-slate-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-calendar-alt text-blue-400"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white text-xs md:text-sm">{{ \Carbon\Carbon::parse($apontamento->data_apontamento)->format('d/m/Y') }}</p>
                        <p class="text-[10px] md:text-xs text-slate-400 uppercase">{{ \Carbon\Carbon::parse($apontamento->data_apontamento)->translatedFormat('l') }}</p>
                    </div>
                </div>
            </div>

            {{-- Horário (Ocupa 1/4 da grade) --}}
            <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50">
                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3">Horário</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-slate-700 border border-slate-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-blue-400"></i>
                    </div>
                    <div>
                        <p class="font-mono text-xs md:text-sm">
                            <span class="text-emerald-400 font-bold">{{ substr($apontamento->hora_inicio, 0, 5) }}</span>
                            <span class="text-slate-600 mx-1">→</span>
                            <span class="text-red-400 font-bold">{{ $apontamento->hora_termino ? substr($apontamento->hora_termino, 0, 5) : '??:??' }}</span>
                        </p>
                        <span class="inline-flex items-center gap-1 mt-1 px-1.5 md:px-2 py-0.5 rounded bg-blue-500/20 text-[10px] md:text-xs font-bold text-blue-300 border border-blue-500/30">
                            <i class="fas fa-hourglass-half text-[8px] md:text-[10px]"></i>
                            {{ $duracao_total }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Linha Completa: Obra / Local de Trabalho --}}
        <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50 mb-3 md:mb-4">
            <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3 flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-blue-400 mr-1"></i> Local de Trabalho / Obra
            </p>
            <p class="text-xs md:text-sm text-white break-words">
                <span class="text-blue-400 font-bold">
                    {{ $apontamento->local_execucao === 'INT' ? 'DENTRO DA OBRA' : 'FORA DA OBRA' }}
                </span>
                <span class="text-slate-600 mx-1 md:mx-2">|</span>
                <span class="text-slate-300">
                    @php
                    $nomeObra = null;
                    $codigoObra = null;

                    // Tenta pegar do projeto primeiro
                    if ($apontamento->projeto) {
                        $nomeObra = $apontamento->projeto->nome;
                        $codigoObra = $apontamento->projeto->codigo;
                    } 
                    // Se não tiver, pega do cliente
                    elseif ($apontamento->codigoCliente) {
                        $nomeObra = $apontamento->codigoCliente->nome;
                        $codigoObra = $apontamento->codigoCliente->codigo;
                    } 
                    // Senão, pega do centro de custo
                    elseif ($apontamento->centroCusto) {
                        $nomeObra = $apontamento->centroCusto->nome;
                        $codigoObra = $apontamento->centroCusto->codigo;
                    }
                    @endphp

                    {{ $nomeObra ?? '' }}
                    @if($nomeObra && $codigoObra)
                        -
                    @endif
                    {{ $codigoObra ?? '' }}
                </span>
            </p>
        </div>

        @php
            $equipe = collect();
            if ($apontamento->auxiliar) {
                $equipe->push($apontamento->auxiliar);
            }
            if ($apontamento->auxiliaresExtras && $apontamento->auxiliaresExtras->isNotEmpty()) {
                $equipe = $equipe->concat($apontamento->auxiliaresExtras);
            }
        @endphp

        @if($equipe->isNotEmpty())
        {{-- Linha Completa: Auxiliares --}}
        <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50 mb-3 md:mb-4">
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Auxiliares</label>
            <div class="flex flex-wrap gap-2">
                @foreach($equipe as $auxiliar)
                    <span class="inline-flex items-center px-3 py-1 bg-slate-800 border border-slate-700 rounded-full text-[10px] md:text-xs font-medium text-slate-300">
                        <i class="fas fa-user-friends text-indigo-400 mr-2"></i>
                        {{ $auxiliar->nome_completo ?? $auxiliar->nome ?? 'Auxiliar' }}
                    </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Grid inferior: Observações + Adicionais --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">

            {{-- Observações do Colaborador --}}
            <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50">
                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3 flex items-center gap-2">
                    <i class="fas fa-comment-alt text-blue-400 mr-1"></i> Observações do Colaborador
                </p>
                @if($apontamento->ocorrencias)
                <p class="text-xs md:text-sm text-slate-300 leading-relaxed bg-slate-900 rounded-lg p-2.5 md:p-3 border border-slate-700">
                    {{ $apontamento->ocorrencias }}
                </p>
                @else
                <p class="text-xs md:text-sm text-slate-600 italic">Nenhuma observação registrada.</p>
                @endif
            </div>

            {{-- Informações Adicionais --}}
            <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50">
                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-400 mr-1"></i> Informações Adicionais
                </p>
                <div class="flex flex-wrap gap-2">
                    @if($apontamento->centroCusto)
                    <span class="inline-flex items-center gap-1 px-2 md:px-2.5 py-1 rounded-full text-[10px] md:text-xs font-bold bg-slate-700 text-slate-300 border border-slate-600">
                        <i class="fas fa-map-marked text-blue-400"></i>
                        {{ $apontamento->centroCusto->nome }}
                    </span>
                    @endif

                    @if($apontamento->veiculo)
                    <span class="inline-flex items-center gap-1 px-2 md:px-2.5 py-1 rounded-full text-[10px] md:text-xs font-bold bg-slate-700 text-slate-300 border border-slate-600">
                        <i class="fas fa-car text-emerald-400"></i>
                        {{ $apontamento->veiculo }}
                    </span>
                    @endif

                    @if($apontamento->veiculo_manual_placa)
                    <span class="inline-flex items-center gap-1 px-2 md:px-2.5 py-1 rounded-full text-[10px] md:text-xs font-bold bg-slate-700 text-slate-300 border border-slate-600">
                        <i class="fas fa-car text-slate-400"></i>
                        {{ $apontamento->veiculo_manual_placa }}
                        @if($apontamento->veiculo_manual_modelo) — {{ $apontamento->veiculo_manual_modelo }} @endif
                    </span>
                    @endif

                    @if($apontamento->em_plantao)
                    <span class="inline-flex items-center gap-1 px-2 md:px-2.5 py-1 rounded-full text-[10px] md:text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                        <i class="fas fa-clipboard-check text-purple-400"></i>
                        Em Plantão
                    </span>
                    @endif

                    @if($apontamento->dorme_fora)
                    <span class="inline-flex items-center gap-1 px-2 md:px-2.5 py-1 rounded-full text-[10px] md:text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        <i class="fas fa-moon text-yellow-400"></i>
                        Dorme Fora
                    </span>
                    @endif

                    @if(!$apontamento->veiculo && !$apontamento->veiculo_manual_placa && !$apontamento->em_plantao && !$apontamento->dorme_fora && !$apontamento->centroCusto)
                    <p class="text-xs md:text-sm text-slate-600 italic w-full">Nenhuma informação adicional.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- Auditoria de Aprovação --}}
        @if(isset($apontamento->status_aprovacao) && $apontamento->status_aprovacao === 'APROVADO' && !empty($apontamento->tipo_aprovacao))
        <div class="bg-slate-900/50 rounded-lg p-3 md:p-4 border border-slate-700/50 mt-3 md:mt-4">
            <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 md:mb-3 flex items-center gap-2">
                <i class="text-blue-400 fas fa-shield-alt"></i>
                Auditoria de Aprovação
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-slate-900/70 rounded-lg p-3 border border-slate-700/50 flex items-center gap-3">
                    @if($apontamento->tipo_aprovacao === 'automatica')
                    <i class="fas fa-gears text-blue-400/70 w-5 text-center"></i>
                    <div>
                        <span class="block text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-0.5">Origem</span>
                        <span class="text-xs font-semibold text-white">SISTEMA</span>
                    </div>
                    @else
                    <i class="fas fa-pen-to-square text-blue-400/70 w-5 text-center"></i>
                    <div>
                        <span class="block text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-0.5">Origem</span>
                        <span class="text-xs font-semibold text-white">MANUAL</span>
                    </div>
                    @endif
                </div>
                <div class="bg-slate-900/70 rounded-lg p-3 border border-slate-700/50 flex items-center gap-3">
                    <i class="fas fa-user-check text-blue-400/70 w-5 text-center"></i>
                    <div>
                        <span class="block text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-0.5">Aprovado por</span>
                        <span class="text-xs font-semibold text-white">{{ $apontamento->tipo_aprovacao === 'automatica' ? 'SISTEMA' : ($apontamento->aprovador->name ?? 'DESCONHECIDO') }}</span>
                    </div>
                </div>
                <div class="bg-slate-900/70 rounded-lg p-3 border border-slate-700/50 flex items-center gap-3">
                    <i class="fas fa-clock text-blue-400/70 w-5 text-center"></i>
                    <div>
                        <span class="block text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-0.5">Data/Hora</span>
                        <span class="text-xs font-semibold text-white">{{ $apontamento->data_aprovacao ? \Carbon\Carbon::parse($apontamento->data_aprovacao)->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ============================================================
         BLOCO 3: COMENTÁRIO DA GESTÃO (Formulário)
         Mobile-first: Botões empilhados (flex-col-reverse)
         ============================================================ --}}
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-4 md:p-6 mb-8">

        {{-- Título do card --}}
        <div class="flex items-center gap-3 mb-4 md:mb-5 pb-3 md:pb-4 border-b border-slate-700/50">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-slate-700 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-gavel text-blue-400 text-sm md:text-lg"></i>
            </div>
            <div>
                <h2 class="text-base md:text-lg font-bold text-white leading-tight">Comentário da Gestão</h2>
                <p class="text-[10px] md:text-xs text-slate-400 mt-0.5">A justificativa é obrigatória para Rejeitar.</p>
            </div>
        </div>

        @if($apontamento->status_aprovacao === 'EM_ANALISE' || ($apontamento->status_aprovacao === 'APROVADO' && $apontamento->tipo_aprovacao === 'automatica'))
        <form method="POST" action="{{ route('aprovacoes.processar', $apontamento->id) }}" id="form-analise">
            @csrf

            {{-- Textarea — Seção 5.5 do Design System --}}
            <div class="mb-5 md:mb-6">
                <label class="block text-xs md:text-sm font-medium mb-1.5 md:mb-2 text-slate-400">
                    Comentário / Motivo
                </label>
                <textarea name="motivo_rejeicao" id="campo-comentario" rows="4"
                          placeholder="Descreva o motivo da sua decisão (obrigatório se rejeitar)..."
                          class="w-full px-3 py-2 md:px-4 md:py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none bg-slate-900 border-slate-700 text-white placeholder-slate-600"></textarea>
            </div>

            {{-- Botões de Ação — Seção 5.6
                 MOBILE: flex-col-reverse (Aprovar em cima, Rejeitar embaixo)
                 DESKTOP: flex-row (Rejeitar na esquerda, Aprovar na direita) --}}
            <div class="flex flex-col-reverse sm:flex-row gap-3">
                
                {{-- Botão Rejeitar (outline vermelho) --}}
                <button type="submit" name="acao" value="REJEITAR" id="btn-recusar"
                        class="w-full @if($apontamento->status_aprovacao === 'EM_ANALISE') sm:w-1/2 @else sm:w-auto sm:px-8 @endif flex items-center justify-center gap-2 px-4 py-3 h-12 text-sm md:text-base font-medium rounded-lg border transition hover:bg-red-500/10 active:bg-red-500/20 border-red-500 text-red-500">
                    <i class="fas fa-times-circle"></i>
                    Rejeitar Registro
                </button>

                @if($apontamento->status_aprovacao === 'EM_ANALISE')
                {{-- Botão Aprovar (solid verde/success) --}}
                <button type="submit" name="acao" value="APROVAR"
                        class="w-full sm:w-1/2 flex items-center justify-center gap-2 px-4 py-3 h-12 text-sm md:text-base font-bold text-white bg-green-600 rounded-lg hover:bg-green-500 active:bg-green-700 transition shadow-lg shadow-green-900/20">
                    <i class="fas fa-check-circle"></i>
                    Aprovar Registro
                </button>
                @endif
            </div>

        </form>
        @else
            <!-- Visualização Apenas Leitura -->
            <div class="mb-5 md:mb-6">
                <label class="block text-xs md:text-sm font-medium mb-1.5 md:mb-2 text-slate-400">
                    Comentário / Motivo (Salvo)
                </label>
                <div class="w-full p-3 md:p-4 bg-slate-900/80 border border-slate-700/50 rounded-lg text-sm text-slate-300 min-h-[80px]">
                    {{ $apontamento->motivo_rejeicao ?: 'Nenhum comentário foi adicionado.' }}
                </div>
            </div>
            
            <div class="flex items-center justify-end w-full mt-4">
                @if($apontamento->status_aprovacao === 'APROVADO')
                    <span class="flex items-center gap-2 px-4 py-3 bg-emerald-500/20 text-emerald-400 font-bold rounded-lg border border-emerald-500/30">
                        <i class="fas fa-check-circle"></i> Registro Aprovado
                    </span>
                @else
                    <span class="flex items-center gap-2 px-4 py-3 bg-red-500/20 text-red-400 font-bold rounded-lg border border-red-500/30">
                        <i class="fas fa-times-circle"></i> Registro Rejeitado
                    </span>
                @endif
            </div>
        @endif

    </div>

    {{-- Botão Voltar inferior --}}
    <div class="text-center mt-6 mb-8 md:mt-8 md:mb-12">
        <a href="{{ route('aprovacoes.dashboard') }}"
           class="inline-flex items-center gap-2 px-5 py-3 h-11 bg-slate-700 hover:bg-slate-600 active:bg-slate-500 rounded-lg font-medium transition text-sm">
            <i class="fas fa-arrow-left"></i>
            Voltar às Aprovações
        </a>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnRecusar = document.getElementById('btn-recusar');
    const campoComentario = document.getElementById('campo-comentario');

    if(btnRecusar && campoComentario) {
        btnRecusar.addEventListener('click', function(e) {
            if (campoComentario.value.trim() === '') {
                e.preventDefault();
                alert('É obrigatório informar o motivo ao rejeitar um apontamento.');
                campoComentario.focus();
            }
        });
    }
});
</script>
@endpush
