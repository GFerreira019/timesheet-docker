@extends('layouts.app')

@section('title', 'Histórico de Apontamentos')

@push('head')
<style>
.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}
/* Ocultar barra de rolagem nos filtros no mobile */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@endpush

@section('content')


<x-page-header 
    backUrl="{{ route('painel') }}" 
    icon="fas fa-list" 
    iconColor="text-indigo-400" 
    title="Histórico" 
    subtitle="Gerencie os apontamentos e histórico da equipe">
</x-page-header>

<div class="w-full">
    {{-- ============================================================
         TOOLBAR PRINCIPAL (Filtros e Ações)
         ============================================================ --}}
    <div class="flex w-full items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-700/50">
        
        <!-- Esquerda: Filtros -->
        <div class="flex items-center gap-2">
            @foreach(['3' => '3 Dias', '7' => '7 Dias', '30' => '30 Dias'] as $p => $label)
            <a href="?period={{ $p }}"
               class="hidden sm:flex px-4 py-1.5 rounded-full text-sm font-bold transition flex-shrink-0 
                      {{ $current_period == $p
                         ? 'bg-indigo-600 text-white shadow-md'
                         : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700/50' }}">
                {{ $label }}
            </a>
            @endforeach

            <!-- Filtro Customizado (Calendário) -->
            <div class="relative" id="dropdown-container">
                <!-- Botão Gatilho -->
                <button type="button" id="dateFilterBtn" class="px-3 sm:px-4 py-1.5 rounded-full bg-slate-800 text-slate-300 text-sm font-bold hover:bg-slate-700/50 flex justify-center sm:justify-start items-center gap-2 border border-slate-700 transition">
                    <i class="fas fa-calendar-alt text-indigo-400"></i>
                    <span class="inline">Período</span>
                </button>

                <!-- Dropdown do Formulário -->
                <div id="dateFilterDropdown" class="hidden absolute top-full left-0 mt-2 p-4 bg-slate-800 border border-slate-700 rounded-xl shadow-xl z-50 w-[calc(100vw-3rem)] sm:w-auto sm:min-w-[350px]">
                    <form action="{{ route('historico.index') }}" method="GET" class="flex flex-col gap-3">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-1 font-bold">De</label>
                                <input type="date" name="start_date" value="{{ $current_period === 'custom' ? $start_date_val : '' }}" required
                                       class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[10px] text-slate-400 uppercase tracking-wider mb-1 font-bold">Até</label>
                                <input type="date" name="end_date" value="{{ $current_period === 'custom' ? $end_date_val : '' }}" required
                                       class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                        </div>
                        <div class="flex gap-2 mt-1">
                            <a href="{{ route('historico.index') }}" class="w-1/3 bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg text-sm font-bold transition text-center flex items-center justify-center">
                                Limpar
                            </a>
                            <button type="submit" class="w-2/3 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition">
                                Aplicar Filtro
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Direita: Ações -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('apontamentos.create') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition shadow-lg shadow-indigo-900/20 text-sm whitespace-nowrap">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline">Novo Registro</span>
            </a>
            <x-notificacoes-bell />
        </div>
    </div>

    @if($bloqueia_data_antiga ?? false)
    <div class="mb-4 px-4 py-3 bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-lg text-sm">
        ⚠️ Você só pode consultar os últimos 30 dias. Exibindo período máximo disponível.
    </div>
    @endif

    {{-- ============================================================
         ESTRUTURA A: VISÃO DESKTOP (Tabela Original)
         ============================================================ --}}
    <div class="hidden lg:block overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg">
        <table class="min-w-full divide-y divide-slate-700/50">
            <thead>
                <tr class="bg-slate-900/30">
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Data</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Obra / Justificativa</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Colaborador</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Veículo</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Início</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Fim</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Total</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Total Dia</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Obs</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Extras</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Info</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Ações</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            @if(empty($apontamentos_lista))
            <tbody>
                <tr>
                    <td colspan="13" class="py-16 text-center text-slate-500">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p class="font-medium text-slate-300">Nenhum apontamento encontrado</p>
                        <p class="text-sm mt-1">Tente ampliar o período de busca.</p>
                    </td>
                </tr>
            </tbody>
            @else
                @php
                    $lista = $apontamentos_lista instanceof \Illuminate\Support\Collection 
                                ? $apontamentos_lista->values()->all() 
                                : (is_array($apontamentos_lista) ? array_values($apontamentos_lista) : []);
                @endphp
                @foreach($lista as $index => $item)
                    @php
                        $isSameGroup = false;
                        $hasNextInGroup = false;
                        
                        $currentGroupId = $item['id_agrupamento'] ?? $item['id'];
                        
                        if ($index > 0 && isset($lista[$index - 1])) {
                            $prev = $lista[$index - 1];
                            $prevGroupId = $prev['id_agrupamento'] ?? $prev['id'];
                            if ($currentGroupId == $prevGroupId) {
                                $isSameGroup = true;
                            }
                        }
                        if ($index < count($lista) - 1 && isset($lista[$index + 1])) {
                            $next = $lista[$index + 1];
                            $nextGroupId = $next['id_agrupamento'] ?? $next['id'];
                            if ($currentGroupId == $nextGroupId) {
                                $hasNextInGroup = true;
                            }
                        }
                    @endphp

                    @if(!$isSameGroup)
                        @if($index > 0)
                            </tbody>
                        @endif
                        <tbody class="group/tbody relative divide-y divide-slate-700/50">
                    @endif

                    <tr class="hover:bg-slate-800/50 transition group {{ $item['flag_atencao'] ? 'border-l-4 border-l-yellow-500 bg-yellow-500/10' : '' }}">

                        {{-- Data (Com Fita de Agrupamento) --}}
                        <td class="relative py-3 px-4 text-sm text-slate-300 whitespace-nowrap">
                            @if(!$isSameGroup && !$hasNextInGroup)
                                <div class="absolute left-0 top-1 bottom-1 w-1 bg-gradient-to-b from-slate-500 to-slate-700 hidden lg:block rounded-r-md"></div>
                            @elseif(!$isSameGroup && $hasNextInGroup)
                                <div class="absolute left-0 top-1 bottom-0 w-1 bg-gradient-to-b from-slate-500 to-slate-600 hidden lg:block rounded-br-md"></div>
                            @elseif($isSameGroup && $hasNextInGroup)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-600 hidden lg:block"></div>
                            @elseif($isSameGroup && !$hasNextInGroup)
                                <div class="absolute left-0 top-0 bottom-1 w-1 bg-gradient-to-b from-slate-600 to-slate-800 hidden lg:block rounded-tr-md"></div>
                            @endif

                            {{ \Carbon\Carbon::parse($item['data'])->format('d/m/Y') }}
                        </td>

                        {{-- Obra --}}
                        <td class="py-3 px-4 text-sm text-slate-300">
                            {{ $item['local_ref'] }}
                        </td>

                        {{-- Colaborador --}}
                        <td class="py-3 px-4 text-sm">
                            <div class="flex flex-col">
                                <span class="font-bold {{ $item['is_auxiliar'] ? 'text-indigo-400' : 'text-slate-200' }}">
                                    {{ $item['nome'] }}
                                    @if($item['is_auxiliar'])
                                    <span class="ml-1 text-[10px] text-indigo-400 border border-indigo-800 px-1 rounded">AUX</span>
                                    @endif
                                </span>
                                <span class="text-xs text-slate-500">{{ $item['cargo'] }}</span>
                            </div>
                        </td>

                        {{-- Veículo --}}
                        <td class="py-3 px-4 text-center">
                            <span class="text-emerald-400 font-mono text-xs">{{ $item['veiculo'] ?: '-' }}</span>
                        </td>

                        {{-- Horários --}}
                        <td class="py-3 px-4 text-center">
                            <span class="text-green-500 font-medium font-mono text-sm">
                                {{ $item['inicio'] ? substr($item['inicio'], 0, 5) : '-' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="{{ $item['termino'] ? 'text-red-500' : 'text-yellow-500 animate-pulse' }} font-medium font-mono text-sm">
                                {{ $item['termino'] ? substr($item['termino'], 0, 5) : 'Andamento' }}
                            </span>
                        </td>

                        {{-- Duração --}}
                        <td class="py-3 px-4 text-center bg-slate-800/30">
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-white font-bold font-mono text-sm">{{ $item['duracao'] ?: '-' }}</span>
                                @if($item['flag_atencao'])
                                <div class="relative group/alert cursor-help">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 text-sm animate-pulse"></i>
                                    
                                    <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-64 p-3 bg-slate-800 border border-yellow-500/50 text-left rounded shadow-lg opacity-0 invisible group-hover/alert:opacity-100 group-hover/alert:visible transition-all duration-200 z-[100]">
                                        <div class="flex items-center gap-2 mb-1 border-b border-yellow-500/30 pb-1">
                                            <i class="fas fa-exclamation-triangle text-yellow-500 text-xs"></i>
                                            <span class="font-bold text-yellow-500 text-xs uppercase">Atenção</span>
                                        </div>
                                        <p class="text-xs text-slate-300 leading-relaxed">
                                            {{ $item['motivo_alerta'] }}
                                        </p>
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-800"></div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </td>

                        {{-- Total do Dia --}}
                        <td class="py-3 px-4 text-center align-middle">
                            @if($item['is_last_of_day'] && $item['total_dia_str'])
                            <div class="flex justify-center">
                                <span class="border border-orange-500/30 text-orange-400 px-2 py-1 rounded text-xs font-bold font-mono shadow-sm min-w-[60px]">
                                    {{ $item['total_dia_str'] }}
                                </span>
                            </div>
                            @else
                            <span class="text-slate-600 text-xs select-none">|</span>
                            @endif
                        </td>

                        {{-- Observações --}}
                        <td class="py-3 px-4 text-center">
                            @if($item['obs'])
                            <button type="button" onclick="openModal('Observações', `{{ addslashes($item['obs']) }}`)" class="text-slate-400 hover:text-white transition-colors" title="Ver Observação">
                                <i class="fas fa-comment-alt text-lg"></i>
                            </button>
                            @else<span class="text-slate-600 text-xs">-</span>@endif
                        </td>

                        {{-- Extras (plantão / dorme fora) --}}
                        <td class="py-3 px-4 text-center">
                            <div class="flex flex-col items-center justify-center gap-1">
                                @if($item['dorme_fora'])
                                    <i class="fas fa-moon text-indigo-400 text-lg" title="Dorme Fora"></i>
                                @endif
                                @if($item['em_plantao'])
                                    <i class="fas fa-clock text-red-500 text-lg" title="Plantão"></i>
                                @endif
                                @if(!$item['dorme_fora'] && !$item['em_plantao'])
                                    <span class="text-slate-600 text-xs">-</span>
                                @endif
                            </div>
                        </td>

                        {{-- Info (Info & Location) --}}
                        <td class="py-3 px-4 text-center">
                            @if(!$item['is_auxiliar'])
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" 
                                    class="text-cyan-600 hover:text-cyan-400 transition-colors" title="Ver Detalhes do Registro"
                                    @if($is_owner ?? false)
                                    data-tipo-aprovacao="{{ $item['tipo_aprovacao'] ?? '' }}"
                                    data-aprovador-nome="{{ $item['aprovador_nome'] ?? '' }}"
                                    data-data-aprovacao="{{ $item['data_aprovacao'] ? \Carbon\Carbon::parse($item['data_aprovacao'])->format('d/m/Y H:i') : '' }}"
                                    data-status-aprovacao="{{ $item['status_aprovacao'] ?? '' }}"
                                    @endif
                                    onclick="openModal('Detalhes do Registro', `👤 Enviado por: {{ addslashes($item['registrado_por_str'] ?? '') }}\n📅 Data: {{ \Carbon\Carbon::parse($item['registrado_em'])->format('d/m/Y') }}\n⏰ Hora: {{ \Carbon\Carbon::parse($item['registrado_em'])->format('H:i') }}@if($item['latitude'])\n\n📍 Localização Capturada:\nLat: {{ number_format($item['latitude'], 6) }}\nLon: {{ number_format($item['longitude'], 6) }}@endif`, this)">
                                    <i class="fas fa-info-circle text-lg"></i>
                                </button>

                                @if($item['latitude'] && $item['longitude'])
                                    <a href="https://www.google.com/maps?q={{ number_format($item['latitude'], 6, '.', '') }},{{ number_format($item['longitude'], 6, '.', '') }}" 
                                       target="_blank" 
                                       class="text-emerald-400 hover:text-emerald-300 transition-colors"
                                       title="Abrir no Google Maps">
                                        <i class="fas fa-map-marker-alt text-lg"></i>
                                    </a>
                                @endif
                            </div>
                            @else
                                <span class="text-slate-700 text-xs">-</span>
                            @endif
                        </td>

                        {{-- Ações --}}
                        <td class="py-3 px-4 text-center">
                            @if(!$item['is_auxiliar'])
                            <div class="flex items-center justify-center gap-3">
                                @if($item['pode_editar'])
                                    @if(empty($item['termino']))
                                        <span title="Não é possível editar um check-in em andamento. Finalize-o primeiro." class="inline-block cursor-not-allowed">
                                            <button type="button" disabled class="text-slate-500 opacity-50 cursor-not-allowed transition pointer-events-none">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                        </span>
                                    @else
                                        <a href="{{ route('apontamentos.edit', $item['id']) }}" title="Editar" class="transition-transform hover:scale-110 inline-block">
                                            <i class="fas fa-edit text-indigo-400 hover:text-indigo-300 text-lg"></i>
                                        </a>
                                    @endif
                                @elseif(($item['status_aprovacao'] ?? 'EM_ANALISE') != 'SOLICITACAO_AJUSTE' && ($item['registrado_por_id'] == auth()->id() || ($is_owner ?? false)))
                                <button type="button"
                                        onclick="openAjusteModal({{ $item['id'] }})"
                                        title="Solicitar Ajuste" class="transition-transform hover:scale-110">
                                    <i class="fas fa-exclamation-circle text-yellow-500 hover:text-yellow-400 text-lg"></i>
                                </button>
                                @endif
                                
                                @if($is_gestor ?? false)
                                    <a href="{{ route('aprovacoes.analise', $item['id']) }}" title="Analisar Registro" class="transition-transform hover:scale-110">
                                        <i class="fas fa-eye text-blue-400 hover:text-blue-300 text-lg"></i>
                                    </a>
                                @endif

                                @if($is_owner ?? false)
                                <form method="POST" action="{{ route('apontamentos.destroy', $item['id']) }}" class="inline"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este registro permanentemente?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Excluir" class="transition-transform hover:scale-110">
                                        <i class="fas fa-trash text-red-500 hover:text-red-400 text-lg"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                            @else
                                <span class="text-slate-700 text-xs">-</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if(!$item['is_auxiliar'])
                                @php
                                    $status = $item['status_aprovacao'] ?? 'EM_ANALISE';
                                    $statusMap = [
                                        'APROVADO'           => ['text' => 'APROVADO',           'class' => 'bg-green-500/10 text-green-400 border border-green-500/30 px-2.5 py-1 text-xs font-bold rounded-full'],
                                        'REJEITADO'          => ['text' => 'REJEITADO',          'class' => 'bg-red-500/10 text-red-500 border border-red-500/30 px-2.5 py-1 text-xs font-bold rounded-full cursor-help'],
                                        'EM_ANALISE'         => ['text' => 'EM ANÁLISE',         'class' => 'bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 px-2.5 py-1 text-xs font-bold rounded-full'],
                                        'SOLICITACAO_AJUSTE' => ['text' => '⚠ AJUSTE',           'class' => 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 px-2.5 py-1 text-xs font-bold rounded-full cursor-help animate-pulse'],
                                    ];
                                    $s = $statusMap[$status] ?? $statusMap['EM_ANALISE'];
                                @endphp
                                
                                @if($status == 'REJEITADO' || $status == 'SOLICITACAO_AJUSTE')
                                    <div class="group relative inline-block">
                                        <span class="{{ $s['class'] }}">{{ $s['text'] }}</span>
                                        @if($item['motivo_rejeicao'] && $status == 'REJEITADO')
                                        <div class="absolute top-full right-0 mt-2 w-64 p-3 bg-slate-800 text-slate-300 text-xs rounded shadow-xl hidden group-hover:block z-[9999] border border-slate-700 text-left">
                                            <strong class="text-red-500 block mb-1">Motivo:</strong>
                                            <p>{{ $item['motivo_rejeicao'] }}</p>
                                        </div>
                                        @endif
                                        @if($item['motivo_ajuste'] && $status == 'SOLICITACAO_AJUSTE' && ($is_owner ?? false))
                                        <div class="absolute top-full right-0 mt-2 w-64 p-3 bg-slate-800 text-slate-300 text-xs rounded shadow-xl hidden group-hover:block z-[9999] border border-slate-700 text-left">
                                            <strong class="text-indigo-400 block mb-1">Solicitação:</strong>
                                            <p>{{ $item['motivo_ajuste'] }}</p>
                                        </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="{{ $s['class'] }}">{{ $s['text'] }}</span>
                                @endif
                            @else
                                <span class="text-slate-700 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @if($loop->last)
                        </tbody>
                    @endif
                @endforeach
            @endif
        </table>
    </div>

    {{-- ============================================================
         ESTRUTURA B: VISÃO MOBILE (Cards Empilhados)
         ============================================================ --}}
    <div class="block lg:hidden mt-4">
        @forelse($apontamentos_lista as $item)
        @php
            $isAuxiliarConectado = false;
            $hasNextAuxiliar = false;
            $lista = $apontamentos_lista instanceof \Illuminate\Support\Collection 
                        ? $apontamentos_lista->values()->all() 
                        : (is_array($apontamentos_lista) ? array_values($apontamentos_lista) : []);
                        
            if ($loop->index > 0 && isset($lista[$loop->index - 1])) {
                $prev = $lista[$loop->index - 1];
                if ($item['is_auxiliar'] && $item['local_ref'] == $prev['local_ref'] && $item['inicio'] == $prev['inicio']) {
                    $isAuxiliarConectado = true;
                }
            }
            if ($loop->index < count($lista) - 1 && isset($lista[$loop->index + 1])) {
                $next = $lista[$loop->index + 1];
                if ($next['is_auxiliar'] && $next['local_ref'] == $item['local_ref'] && $next['inicio'] == $item['inicio']) {
                    $hasNextAuxiliar = true;
                }
            }

            $status = $item['status_aprovacao'] ?? 'EM_ANALISE';
            $statusMap = [
                'APROVADO'           => ['text' => 'APROVADO',           'class' => 'bg-green-500/10 text-green-400 border border-green-500/30 px-2 py-0.5 text-[10px] font-bold rounded-full'],
                'REJEITADO'          => ['text' => 'REJEITADO',          'class' => 'bg-red-500/10 text-red-500 border border-red-500/30 px-2 py-0.5 text-[10px] font-bold rounded-full'],
                'EM_ANALISE'         => ['text' => 'EM ANÁLISE',         'class' => 'bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 px-2 py-0.5 text-[10px] font-bold rounded-full'],
                'SOLICITACAO_AJUSTE' => ['text' => '⚠ AJUSTE',           'class' => 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 px-2 py-0.5 text-[10px] font-bold rounded-full animate-pulse'],
            ];
            $s = $statusMap[$status] ?? $statusMap['EM_ANALISE'];
        @endphp

        <div class="bg-slate-800 border {{ $item['flag_atencao'] ? 'border-yellow-500/50 shadow-lg shadow-yellow-500/10' : 'border-slate-700/50 shadow-lg' }} p-4 relative flex flex-col w-full max-w-full
            {{ $isAuxiliarConectado ? 'mt-0 rounded-t-none border-t-0 bg-slate-800/80 border-l-4 border-l-indigo-500 pl-3' : 'mt-4 rounded-xl' }}
            {{ $hasNextAuxiliar && !$isAuxiliarConectado ? 'rounded-b-none border-b-0 border-l-4 border-l-emerald-500 pl-3' : ($hasNextAuxiliar ? 'rounded-b-none border-b-0' : '') }}">
            
            {{-- Badge de Atenção Absoluto --}}
            @if($item['flag_atencao'])
            <div class="absolute -top-2 -right-2 bg-yellow-500 text-slate-900 rounded-full w-6 h-6 flex items-center justify-center shadow-lg border-2 border-slate-900 z-10" onclick="openModal('Atenção', `{{ addslashes($item['motivo_alerta']) }}`)">
                <i class="fas fa-exclamation text-xs font-bold"></i>
            </div>
            @endif

            {{-- Cabeçalho do Card --}}
            <div class="flex justify-between items-center border-b border-slate-700/50 pb-2 mb-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-day text-slate-400"></i>
                    <span class="font-bold text-white text-sm">{{ \Carbon\Carbon::parse($item['data'])->format('d/m/Y') }}</span>
                </div>
                <span class="{{ $s['class'] }}">{{ $s['text'] }}</span>
            </div>

            {{-- Corpo do Card --}}
            <div class="flex-1 min-w-0 mb-3 space-y-2">
                {{-- Colaborador --}}
                <div class="flex flex-col min-w-0">
                    <span class="font-bold {{ $item['is_auxiliar'] ? 'text-indigo-400' : 'text-slate-200' }} text-sm break-words">
                        <i class="fas fa-user text-slate-500 mr-1 w-4 text-center"></i>
                        {{ $item['nome'] }}
                        @if($item['is_auxiliar'])
                        <span class="ml-1 text-[10px] text-indigo-400 border border-indigo-800 px-1 rounded">AUX</span>
                        @endif
                    </span>
                    <span class="text-xs text-slate-500 ml-6">{{ $item['cargo'] }}</span>
                </div>

                {{-- Obra / Justificativa --}}
                <div class="text-slate-200 text-sm font-medium flex items-start break-words min-w-0">
                    <i class="fas fa-map-marker-alt text-slate-500 mr-2 mt-1 w-4 text-center shrink-0"></i>
                    <span class="break-words w-full">{{ $item['local_ref'] }}</span>
                </div>

                {{-- Horários --}}
                <div class="flex items-center text-sm ml-6">
                    <i class="fas fa-clock text-slate-500 mr-2"></i>
                    <span class="text-green-500 font-medium font-mono">{{ $item['inicio'] ? substr($item['inicio'], 0, 5) : '-' }}</span>
                    <span class="mx-1 text-slate-500">→</span>
                    <span class="{{ $item['termino'] ? 'text-red-500 font-medium' : 'text-yellow-500 animate-pulse font-bold' }} font-mono">
                        {{ $item['termino'] ? substr($item['termino'], 0, 5) : 'Andamento' }}
                    </span>
                    @if($item['duracao'])
                    <span class="text-white bg-slate-800/30 px-1 rounded font-bold font-mono ml-2">[{{ $item['duracao'] }}]</span>
                    @endif
                </div>

                {{-- Veículo --}}
                @if($item['veiculo'])
                <div class="flex items-center text-xs ml-6 mt-1">
                    <i class="fas fa-car text-slate-500 mr-2"></i>
                    <span class="font-mono text-emerald-400">{{ $item['veiculo'] }}</span>
                </div>
                @endif
                
                {{-- Total do dia --}}
                @if($item['is_last_of_day'] && $item['total_dia_str'])
                <div class="flex items-center text-xs mt-2 pt-2 border-t border-slate-700/50">
                    <span class="text-slate-400 mr-2">Total do Dia:</span>
                    <span class="border border-orange-500/30 text-orange-400 px-2 py-1 rounded text-xs font-bold font-mono shadow-sm">
                        {{ $item['total_dia_str'] }}
                    </span>
                </div>
                @endif
                
                {{-- Rejeição / Ajuste motiv --}}
                @if($status == 'REJEITADO' && $item['motivo_rejeicao'])
                <div class="mt-2 text-xs bg-red-500/10 border border-red-500/30 text-red-400 p-2 rounded-lg">
                    <strong class="text-red-500">Motivo:</strong> {{ $item['motivo_rejeicao'] }}
                </div>
                @endif
                @if($status == 'SOLICITACAO_AJUSTE' && $item['motivo_ajuste'])
                <div class="mt-2 text-xs bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 p-2 rounded-lg">
                    <strong class="text-indigo-400">Ajuste:</strong> {{ $item['motivo_ajuste'] }}
                </div>
                @endif
            </div>

            {{-- Rodapé do Card (Ícones e Ações) --}}
            <div class="flex justify-between items-center mt-auto pt-3 bg-slate-900/50 -mx-4 -mb-4 p-4 border-t border-slate-700/50 {{ $hasNextAuxiliar ? 'rounded-b-none' : 'rounded-b-xl' }}">
                
                {{-- Esquerda (Ícones de Extras/Info) --}}
                <div class="flex items-center gap-2 sm:gap-3">
                    @if($item['dorme_fora'])
                        <i class="fas fa-moon text-indigo-400 text-lg" title="Dorme Fora"></i>
                    @endif
                    @if($item['em_plantao'])
                        <i class="fas fa-clock text-red-500 text-lg" title="Plantão"></i>
                    @endif
                    @if($item['obs'])
                        <button type="button" onclick="openModal('Observações', `{{ addslashes($item['obs']) }}`)" class="text-slate-400 hover:text-white transition-colors" title="Ver Observação">
                            <i class="fas fa-comment-alt text-lg"></i>
                        </button>
                    @endif
                    
                    {{-- Detalhes (Info) — dados de auditoria passados via data-* para owner --}}
                    @if(!$item['is_auxiliar'])
                        <button type="button" 
                            class="text-cyan-600 hover:text-cyan-400 transition-colors" title="Ver Detalhes do Registro"
                            @if($is_owner ?? false)
                            data-tipo-aprovacao="{{ $item['tipo_aprovacao'] ?? '' }}"
                            data-aprovador-nome="{{ $item['aprovador_nome'] ?? '' }}"
                            data-data-aprovacao="{{ $item['data_aprovacao'] ? \Carbon\Carbon::parse($item['data_aprovacao'])->format('d/m/Y H:i') : '' }}"
                            data-status-aprovacao="{{ $item['status_aprovacao'] ?? '' }}"
                            @endif
                            onclick="openModal('Detalhes do Registro', `👤 Enviado por: {{ addslashes($item['registrado_por_str'] ?? '') }}\n📅 Data: {{ \Carbon\Carbon::parse($item['registrado_em'])->format('d/m/Y') }}\n⏰ Hora: {{ \Carbon\Carbon::parse($item['registrado_em'])->format('H:i') }}@if($item['latitude'])\n\n📍 Localização Capturada:\nLat: {{ number_format($item['latitude'], 6) }}\nLon: {{ number_format($item['longitude'], 6) }}@endif`, this)">
                            <i class="fas fa-info-circle text-lg"></i>
                        </button>
                    @else
                        <span> </span>
                    @endif
                    
                    {{-- Maps --}}
                    @if($item['latitude'] && $item['longitude'])
                        <a href="https://www.google.com/maps?q={{ number_format($item['latitude'], 6, '.', '') }},{{ number_format($item['longitude'], 6, '.', '') }}" 
                           target="_blank" 
                           class="text-emerald-400 hover:text-emerald-300 transition-colors"
                           title="Abrir no Google Maps">
                            <i class="fas fa-map-marker-alt text-lg"></i>
                        </a>
                    @endif
                </div>

                {{-- Direita (Ações) --}}
                @if(!$item['is_auxiliar'])
                <div class="flex items-center gap-4">
                    @if($item['pode_editar'])
                        @if(empty($item['termino']))
                            <span title="Não é possível editar um check-in em andamento. Finalize-o primeiro." class="inline-block cursor-not-allowed">
                                <button type="button" disabled class="text-slate-500 opacity-50 cursor-not-allowed transition pointer-events-none">
                                    <i class="fas fa-edit text-lg"></i>
                                </button>
                            </span>
                        @else
                            <a href="{{ route('apontamentos.edit', $item['id']) }}" title="Editar" class="transition-transform hover:scale-110 inline-block">
                                <i class="fas fa-edit text-indigo-400 hover:text-indigo-300 text-lg"></i>
                            </a>
                        @endif
                    @elseif(($item['status_aprovacao'] ?? 'EM_ANALISE') != 'SOLICITACAO_AJUSTE' && ($item['registrado_por_id'] == auth()->id() || ($is_owner ?? false)))
                    <button type="button"
                            onclick="openAjusteModal({{ $item['id'] }})"
                            title="Solicitar Ajuste" class="transition-transform hover:scale-110">
                        <i class="fas fa-exclamation-circle text-yellow-500 hover:text-yellow-400 text-lg"></i>
                    </button>
                    @endif
                    
                    @if($is_gestor ?? false)
                        <a href="{{ route('aprovacoes.analise', $item['id']) }}" title="Analisar Registro" class="transition-transform hover:scale-110">
                            <i class="fas fa-eye text-blue-400 hover:text-blue-300 text-lg"></i>
                        </a>
                    @endif

                    @if($is_owner ?? false)
                    <form method="POST" action="{{ route('apontamentos.destroy', $item['id']) }}" class="inline"
                          onsubmit="return confirm('Tem certeza que deseja excluir este registro permanentemente?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Excluir" class="transition-transform hover:scale-110">
                            <i class="fas fa-trash text-red-500 hover:text-red-400 text-lg"></i>
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </div>

        </div>
        @empty
        <div class="bg-slate-800 rounded-xl border border-slate-700/50 p-8 text-center">
            <i class="fas fa-inbox text-4xl text-slate-600 mb-3"></i>
            <p class="font-medium text-slate-300">Nenhum apontamento encontrado</p>
            <p class="text-sm mt-1 text-slate-400">Tente ampliar o período de busca.</p>
        </div>
        @endforelse
    </div>
    @if($paginador->hasPages())
    <div class="mt-4 px-4 pb-4 w-full">
        {{ $paginador->links() }}
    </div>
    @endif
</div>

{{-- Modal de texto genérico (Observações / Detalhes) --}}
<div id="modal-texto" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-xl bg-slate-800 border border-slate-700 text-left shadow-2xl transition-all w-full sm:my-8 sm:w-full sm:max-w-lg">
          <div class="bg-slate-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-slate-700 sm:mx-0">
                <i class="fas fa-info-circle text-gray-300 text-lg"></i>
              </div>
              <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                <h3 class="text-base font-semibold leading-6 text-white" id="modal-titulo">Info</h3>
                <div class="mt-4">
                    <p id="modal-corpo" class="text-sm text-gray-300 whitespace-pre-wrap bg-slate-900 p-3 rounded-lg border border-slate-700 min-h-[60px] flex items-center text-left"></p>
                </div>

                {{-- Seção de Auditoria de Aprovação (renderizada no HTML apenas para Owner) --}}
                @if($is_owner ?? false)
                <div id="modal-auditoria-secao" class="hidden mt-4">
                    <hr class="border-slate-700 mb-4">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-shield-alt text-purple-400 text-sm"></i>
                        <span class="text-xs font-bold text-purple-400 uppercase tracking-widest">Auditoria de Aprovação</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 bg-slate-900/70 rounded-lg px-3 py-2 border border-slate-700/50">
                            <i class="fas fa-tag text-purple-400/70 text-xs w-4 text-center"></i>
                            <div class="flex gap-2 items-baseline">
                                <span class="text-[10px] uppercase tracking-wider text-slate-500">Origem</span>
                                <span id="modal-auditoria-origem" class="text-xs font-semibold text-white">—</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-900/70 rounded-lg px-3 py-2 border border-slate-700/50">
                            <i class="fas fa-user-check text-purple-400/70 text-xs w-4 text-center"></i>
                            <div class="flex gap-2 items-baseline">
                                <span class="text-[10px] uppercase tracking-wider text-slate-500">Aprovado por</span>
                                <span id="modal-auditoria-aprovador" class="text-xs font-semibold text-white">—</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-900/70 rounded-lg px-3 py-2 border border-slate-700/50">
                            <i class="fas fa-clock text-purple-400/70 text-xs w-4 text-center"></i>
                            <div class="flex gap-2 items-baseline">
                                <span class="text-[10px] uppercase tracking-wider text-slate-500">Data / Hora</span>
                                <span id="modal-auditoria-data" class="text-xs font-semibold text-white">—</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
              </div>
            </div>
          </div>
          <div class="bg-slate-900/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-700/50">
            <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-lg border border-slate-700 hover:bg-slate-700/30 px-4 py-2 text-sm font-medium text-slate-400 shadow-sm transition sm:mt-0 sm:w-auto h-11 sm:h-auto items-center">Fechar</button>
          </div>
        </div>
      </div>
    </div>
</div>

{{-- Modal de Solicitar Ajuste --}}
<div id="modal-ajuste" class="fixed inset-0 z-[9999] bg-gray-900/75 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-xl shadow-2xl w-full max-w-md p-6 relative">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center text-yellow-500">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-lg font-bold text-white">Solicitar Ajuste</h3>
        </div>
        <p class="text-sm text-slate-400 mb-4">Descreva o que está errado neste apontamento.</p>
        <form id="form-ajuste" method="POST" action="">
            @csrf
            <textarea name="motivo_texto" rows="4" class="w-full bg-slate-900 text-slate-300 rounded-lg border border-slate-700 p-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none" required placeholder="Ex: Esqueci de registrar o horário correto..."></textarea>
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-5">
                <button type="button" onclick="document.getElementById('modal-ajuste').classList.add('hidden')" class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-lg border border-slate-700 text-slate-400 hover:bg-slate-700/30 transition h-11 sm:h-auto">Cancelar</button>
                <button type="submit" class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition h-11 sm:h-auto">Enviar</button>
            </div>
        </form>
    </div>
</div>


@endsection

@push('scripts')
<script>
// Função unificada para abrir o modal genérico
// Recebe o título, o corpo do texto e, opcionalmente, o botão (para ler os data-attributes de auditoria)
function openModal(titulo, corpo, btn = null) {
    document.getElementById('modal-titulo').textContent = titulo;
    document.getElementById('modal-corpo').textContent = corpo;

    // Lógica da Seção de Auditoria (protegida para não quebrar usuários comuns)
    const secaoAuditoria = document.getElementById('modal-auditoria-secao');
    if (secaoAuditoria) {
        let temAuditoria = false;

        if (btn) {
            const tipo   = btn.dataset.tipoAprovacao  || '';
            const nome   = btn.dataset.aprovadorNome  || '';
            const data   = btn.dataset.dataAprovacao  || '';
            const status = btn.dataset.statusAprovacao || '';

            temAuditoria = (status === 'APROVADO') && tipo;

            if (temAuditoria) {
                const origemMap = {
                    'automatica': '🤖 Sistema Automático (Cron CLT)',
                    'manual':     '👤 Aprovação Manual pelo Gestor',
                };
                document.getElementById('modal-auditoria-origem').textContent    = origemMap[tipo] ?? tipo;
                document.getElementById('modal-auditoria-aprovador').textContent = (tipo === 'automatica' || !nome) ? 'N/A (Sistema)' : nome;
                document.getElementById('modal-auditoria-data').textContent      = data || '—';
            }
        }

        // Exibe ou oculta a div de auditoria dependendo de haver dados
        secaoAuditoria.classList.toggle('hidden', !temAuditoria);
    }

    // Exibe o modal na tela (via Tailwind class)
    document.getElementById('modal-texto').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('modal-texto').classList.add('hidden');
}
function openAjusteModal(id) {
    const form = document.getElementById('form-ajuste');
    form.action = `/apontamentos/${id}/ajuste`;
    document.getElementById('modal-ajuste').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('dateFilterBtn');
    const dropdown = document.getElementById('dateFilterDropdown');
    const container = document.getElementById('dropdown-container');

    if (btn && dropdown && container) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }
});
</script>
@endpush
