@extends('layouts.app')

@section('title', $titulo)

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
{{-- jQuery + Select2 para searchable dropdowns (mesmo padrão do Django) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .header-gradient {
        background: linear-gradient(
            135deg,
            rgba(30,41,59,.95) 0%,
            rgba(15,23,42,.98) 100%
        );
    }
    /* Estilos Gerais (Inputs, Selects, Textarea) - Ajuste de Altura e Fonte (Mobile-First) */
    .form-input, .form-control, select, input[type="text"], input[type="date"], input[type="time"] {
        background-color: #1e293b; /* bg-slate-800 */
        border: 1px solid #334155; /* border-slate-700 */
        color: #ffffff;
        border-radius: 0.5rem; /* rounded-lg */
        padding: 0 0.75rem; /* px-3: Remove paddings verticais */
        height: 46px; /* h-[46px] fixo OBRIGATÓRIO */
        width: 100%; 
        display: block;
        font-size: 0.875rem; /* text-sm para caber textos longos no mobile */
        transition: all 0.2s;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis; /* truncate nativo */
    }
    textarea.form-input, textarea.form-control, textarea {
        background-color: #1e293b;
        border: 1px solid #334155;
        color: #ffffff;
        border-radius: 0.5rem;
        padding: 0.75rem;
        width: 100%;
        display: block;
        font-size: 0.875rem;
        transition: all 0.2s;
        height: auto;
    }
    .form-input:focus, .form-control:focus, textarea:focus {
        outline: none;
        box-shadow: 0 0 0 2px #4f46e5; /* ring-indigo-600 */
        border-color: #4f46e5;
    }
    input[type="date"]::-webkit-calendar-picker-indicator, input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }
    .form-label {
        font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.25rem; display: block; font-weight: bold;
    }

    /* Select2 Dark Theme — Ajuste de Altura e Fonte (Fixo 46px) */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        background-color: #1e293b !important; 
        border-color: #334155 !important;
        color: #ffffff !important; 
        height: 46px; /* h-[46px] fixo */
        border-radius: 0.5rem; 
        display: flex; 
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #ffffff !important; line-height: 46px; padding-left: 12px; font-size: 14px; /* text-sm */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }

    .select2-dropdown { background-color: #1e293b !important; border-color: #334155 !important; color: #e2e8f0; border-radius: 0.5rem; }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #0f172a !important; border: 1px solid #334155 !important; color: white !important; border-radius: 0.5rem;
    }
    .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #4f46e5 !important; color: white !important;
    }
    .select2-results__option { color: #e2e8f0 !important; }
    .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    #mode-toggle:checked ~ #switch-knob .icon-manual { opacity: 0; transform: scale(0.5) rotate(-90deg); }
    #mode-toggle:checked ~ #switch-knob .icon-checkin { opacity: 1; transform: scale(1) rotate(0deg); }
</style>
@endpush

@section('content')
{{-- Loader de sincronização --}}
<div id="page-loader"
     class="fixed inset-0 bg-slate-950 z-[9999] flex flex-col items-center justify-center transition-opacity duration-500">
    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500 mb-4"></div>
    <p class="text-indigo-400 font-bold animate-pulse">Sincronizando...</p>
</div>

{{-- Toast container --}}
<div id="toast-container" class="fixed top-5 right-5 z-[100] space-y-2 w-80"></div>

<x-page-header 
    backUrl="{{ route('painel') }}" 
    icon="fas fa-clock" 
    iconColor="text-indigo-400" 
    title="{{ $titulo ?? 'Novo Apontamento' }}" 
    subtitle="{{ $is_editing ? 'Editando Registro #' . $apontamento_id : 'Preencha as informações de atividades nos projetos' }}">
</x-page-header>

<div class="max-w-5xl mx-auto w-full px-4 sm:px-8 pb-2">
    
    
    <div class="flex items-center justify-between w-full mt-1 mb-0">
        @if(!$is_editing)
        <div class="{{ $atividade_em_andamento ? 'opacity-50 pointer-events-none' : '' }}">
            <label class="relative inline-flex items-center cursor-pointer w-28 h-8 rounded-full">
                <!-- 1. INPUT (O "Chefe" que controla os estados) -->
                <input type="checkbox" id="mode-toggle" class="sr-only peer" {{ $atividade_em_andamento ? 'checked disabled' : 'checked' }}>
                
                <!-- 2. TRILHA DO FUNDO (Irmão 1) -->
                <div class="absolute inset-0 bg-slate-800 border border-slate-700 rounded-full shadow-inner peer-focus:ring-2 peer-focus:ring-indigo-500/50 transition-colors peer-checked:bg-slate-800"></div>

                <!-- 3. TEXTO MANUAL (Irmão 2) -->
                <!-- Fica transparente por padrão, aparece (opacity-100) quando o botão está Checked (na direita) -->
                <span class="absolute left-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest opacity-0 peer-checked:opacity-100 transition-opacity duration-300 pointer-events-none select-none z-10">
                    Manual
                </span>
                
                <!-- 4. TEXTO CHECK-IN (Irmão 3) -->
                <!-- Visível por padrão, fica transparente (opacity-0) quando o botão está Checked -->
                <span class="absolute right-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest opacity-100 peer-checked:opacity-0 transition-opacity duration-300 pointer-events-none select-none z-10">
                    Check-in
                </span>

                <!-- 5. KNOB / BOTÃO DESLIZANTE (Irmão 4) -->
                <div id="switch-knob" class="absolute left-1 top-1 bg-gradient-to-br from-indigo-500 to-indigo-600 w-6 h-6 rounded-full shadow-lg transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] peer-checked:translate-x-20 peer-checked:from-emerald-500 peer-checked:to-emerald-600 flex items-center justify-center text-white z-20 pointer-events-none">
                    
                    <!-- Ícones (A troca das classes de rotação e escala destes ícones é feita pelo seu JavaScript original, que continuará a funcionar intacto) -->
                    <svg class="icon-manual w-3.5 h-3.5 absolute transition-all duration-300 opacity-100 scale-100 rotate-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    
                    <svg class="icon-checkin w-4 h-4 absolute transition-all duration-300 opacity-0 scale-50 rotate-90" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </label>
        </div>
        @else
        <div></div>
        @endif
        
        <div class="flex justify-end gap-4">
            @role('ADMIN')
            <button type="button" onclick="openTimelineModal()" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition shadow-lg shadow-emerald-900/20 text-sm whitespace-nowrap">
                <i class="fas fa-clock"></i>
                <span class="hidden sm:inline">Comparativo Diário</span>
            </button>
            @endrole
            <a href="{{ route('historico.index') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition shadow-lg shadow-indigo-900/20 text-sm whitespace-nowrap">
                <i class="fas fa-list"></i>
                <span class="hidden sm:inline">Histórico</span>
            </a>
            <x-notificacoes-bell />
        </div>
    </div>
</div>

<div class="max-w-5xl mx-auto w-full px-4 sm:px-8 pb-6">
    {{-- Modal de Conflito de Horário (Substituto do mark_safe do Django) --}}
    @if(session('conflito_details'))
    @php $conflito = session('conflito_details'); @endphp
    <div id="modal-conflito" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm animate-fadeIn">
        <div class="bg-slate-900 border border-red-500/50 rounded-2xl p-6 shadow-2xl max-w-lg w-full mx-4 transform transition-all scale-100">
            <div class="flex items-center gap-3 text-red-400 mb-4 border-b border-slate-800 pb-3">
                <svg class="w-8 h-8 flex-shrink-0 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-xl font-bold uppercase tracking-wide">{{ $conflito['tipo'] ?? 'Conflito de Horário' }}</h3>
            </div>
            
            <div class="bg-slate-800/80 p-4 rounded-xl border border-red-500/30 text-sm space-y-3 mb-5 shadow-inner">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span class="font-bold text-white tracking-wide text-base">{{ mb_strtoupper($conflito['colaborador'] ?? 'Colaborador') }}</span>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <span class="text-gray-300 font-medium">{{ $conflito['referencia'] ?? 'Apontamento Existente' }}</span>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span class="text-gray-300">{{ $conflito['data'] ?? '' }}</span>
                </div>
                <div class="flex items-center gap-3 bg-red-950/30 p-2 rounded-lg border border-red-900/30">
                    <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="font-mono text-white font-bold text-lg">{{ $conflito['inicio'] ?? '--:--' }} – {{ $conflito['termino'] ?? '--:--' }}</span>
                </div>
            </div>
            
            @if ($conflito['tipo'] == 'Conflito de Intervalo (Sólides)')
            <p class="text-sm text-red-300/80 italic mb-6">Este apontamento conflita com o intervalo de almoço. Para prosseguir, ajuste os horários para que não haja sobreposição.</p>
            @else
            <p class="text-sm text-red-300/80 italic mb-6">Ajuste os horários. O sistema não permite registrar horas sobrepostas para a mesma pessoa.</p>
            @endif
            
            <div class="flex justify-end">
                <button type="button" onclick="document.getElementById('modal-conflito').remove()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-lg border border-slate-700 transition-colors">
                    Entendi, vou ajustar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Comparativo Diário --}}
    <div id="modal-timeline" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-slate-950/80 backdrop-blur-sm animate-fadeIn">
        <div class="relative w-full max-w-6xl mx-4 max-h-[90vh] overflow-y-auto bg-[#0d1321] border border-slate-700 rounded-2xl shadow-2xl p-2 md:p-6">
            <button type="button" onclick="closeTimelineModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white z-50">
                <i class="fas fa-times text-2xl"></i>
            </button>
            
            <div class="w-full overflow-x-auto bg-[#0d1321] p-4 md:p-8 rounded-xl my-2">
                <div class="mb-8 md:mb-16 border-l-4 border-emerald-500 pl-4 sticky left-0">
                    <h2 class="text-2xl font-bold text-white">Comparativo Diário</h2>
                    <div class="flex gap-4 mt-2 text-sm">
                        <span class="flex items-center gap-1 text-emerald-400"><i class="fas fa-circle text-[8px]"></i> Sólides</span>
                        <span class="flex items-center gap-1 text-blue-400"><i class="fas fa-circle text-[8px]"></i> Timesheet</span>
                    </div>
                </div>

                <div class="mt-8 relative min-w-[800px] py-24 flex items-center px-4">
                    {{-- Linha e Seta --}}
                    <div class="absolute left-0 right-10 top-1/2 h-1 bg-slate-300 -translate-y-1/2 z-0"></div>
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 w-0 h-0 border-t-[10px] border-t-transparent border-l-[16px] border-l-emerald-500 border-b-[10px] border-b-transparent z-10"></div>

                    {{-- LOOP ENTRA AQUI --}}
                    <div class="w-full flex justify-between relative z-10">
                    @if(isset($timeline_data) && count($timeline_data) > 0)
                        @foreach($timeline_data as $item)
                            @php
                                $has_ts = count($item['timesheet_data']) > 0;
                                $isEmbaixo = $loop->even;
                            @endphp
                            
                            <div class="relative z-10 flex items-center shrink-0 {{ !$has_ts ? 'w-32 justify-center' : 'mx-2' }}">
                                
                                {{-- BOLINHA INICIAL (Nó âncora) --}}
                                @if($item['is_solides'])
                                    <div class="relative flex flex-col items-center z-10">
                                        <div class="absolute bottom-full mb-4 flex flex-col items-center w-40">
                                            <span class="text-emerald-400 font-bold text-xl tracking-wider">{{ $item['hora'] }}</span>
                                            <span class="text-slate-200 text-sm font-medium mt-1">{{ $item['solides_data']['titulo'] }}</span>
                                            <span class="text-slate-400 text-xs text-center leading-tight mt-1">{{ $item['solides_data']['subtitulo'] }}</span>
                                            <div class="h-10 w-px border-l border-dashed border-slate-400 mt-3"></div>
                                        </div>
                                        <div class="w-4 h-4 bg-slate-300 rounded-full border-[3px] border-slate-950 shadow-[0_0_0_3px_#34d399] z-20 relative"></div>
                                    </div>
                                @else
                                    <div class="relative flex flex-col items-center z-30">
                                        {{-- Bolinha com contraste (miolo cinza, borda escura, sombra azul) para não sumir na barra --}}
                                        <div class="w-4 h-4 bg-slate-300 rounded-full border-[3px] border-slate-950 shadow-[0_0_0_3px_#3b82f6] z-20 relative"></div>
                                        
                                        <div class="absolute flex items-center {{ $isEmbaixo ? 'top-full mt-1 flex-col' : 'bottom-full mb-1 flex-col-reverse' }}">
                                            <div class="h-8 w-px border-l border-dashed border-[#3b82f6]"></div>
                                            <span class="text-[#3b82f6] font-bold text-xl tracking-wider {{ $isEmbaixo ? 'mt-1' : 'mb-1' }}">{{ $item['hora'] }}</span>
                                        </div>
                                    </div>
                                @endif

                                {{-- BARRA TIMESHEET (Se houver) --}}
                                @if($has_ts)
                                    @foreach($item['timesheet_data'] as $ts)
                                        @php
                                            $is_connected_next = false;
                                            if (isset($timeline_data[$loop->parent->index + 1])) {
                                                $next = $timeline_data[$loop->parent->index + 1];
                                                if ($next['hora'] == $ts['hora_fim']) {
                                                    $is_connected_next = true;
                                                }
                                            }
                                        @endphp
                                        
                                        {{-- Linha Grossa Central --}}
                                        <div class="relative h-5 bg-[#3b82f6] flex items-center justify-center min-w-[100px] px-3 -mx-3 z-0">
                                            <span class="relative z-10 text-white text-xs font-bold tracking-widest truncate pl-6">{{ $ts['codigo'] ?? 'S/ COD' }}</span>
                                            
                                            @if($is_connected_next)
                                                <div class="absolute top-0 left-[50%] w-[200px] h-full bg-[#3b82f6]"></div>
                                            @endif
                                        </div>
                                        
                                        {{-- Bolinha Final (Só se NÃO conectar) --}}
                                        @if(!$is_connected_next)
                                            <div class="relative flex flex-col items-center z-20">
                                                <div class="w-4 h-4 bg-[#3b82f6] rounded-full border-[3px] border-[#3b82f6] shadow-[0_0_0_2px_#3b82f6]"></div>
                                                <div class="absolute flex items-center {{ $isEmbaixo ? 'top-full mt-1 flex-col' : 'bottom-full mb-1 flex-col-reverse' }}">
                                                    <div class="h-8 w-px border-l border-dashed border-[#3b82f6]"></div>
                                                    <span class="text-[#3b82f6] font-bold text-xl tracking-wider {{ $isEmbaixo ? 'mt-1' : 'mb-1' }}">{{ $ts['hora_fim'] }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                                
                            </div>
                        @endforeach
                    @else
                        <p class="text-slate-400 text-sm bg-[#0d1321] px-4 py-2 relative z-20">Nenhum dado encontrado para hoje.</p>
                    @endif
                    </div>
                    {{-- FIM DO LOOP --}}
                </div>
            </div>
        </div>
    </div>
    {{-- Container Estático de Erros (Previne quebra de DOM Morphing) --}}
    <div id="validation-errors-container">
        @if($errors->any())
        <div class="mb-5 px-4 py-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl text-sm">
            <p class="font-bold mb-1">Corrija os erros abaixo:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>

    <form method="POST" action="{{ $is_editing ? route('apontamentos.update', $apontamento_id) : route('apontamentos.store') }}" 
          class="space-y-6" id="apontamentoForm">
        @csrf
        @if($is_editing) @method('PUT') @endif

        <input type="hidden" name="tipo_acao" id="id_tipo_acao" value="{{ $tipo_acao_inicial ?? 'MANUAL' }}">
        <input type="hidden" name="latitude" id="id_latitude" value="{{ old('latitude', $initial_values['latitude'] ?? '') }}">
        <input type="hidden" name="longitude" id="id_longitude" value="{{ old('longitude', $initial_values['longitude'] ?? '') }}">
        <input type="hidden" name="data_plantao" id="id_data_plantao" value="{{ old('data_plantao') }}">
        <input type="hidden" name="data_dorme_fora" id="id_data_dorme_fora" value="{{ old('data_dorme_fora') }}">

        {{-- ================================================================ --}}
        {{-- BLOCO 1: Identificação --}}
        {{-- ================================================================ --}}
        {{-- MIGRADO: a variável $is_owner já é passada pelo controller (AcessoHelper::isOwner) --}}
        {{-- O campo de colaborador é editável para gestores, bloqueado para operacionais --}}
        
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden">

            <!-- Status do GPS Discreto -->
            <div class="flex justify-end -mt-4">
                <i id="gps-status-icon" class="fas fa-map-marker-alt text-slate-500 animate-pulse text-sm" title="Aguardando sinal de GPS..."></i>
            </div>

            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
            <h3 class="text-indigo-400 font-bold mb-6 text-lg uppercase tracking-wider">Identificação</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                {{-- Campo bloqueado para Operacional; aberto para Gestores e superiores --}}
                @php $colabLogado = auth()->user()->colaborador; @endphp
                @if(!$pode_lancar_terceiros)
                    <!-- Visão Estática para perfis sem acesso expandido -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-white font-bold mb-2">Colaborador <span class="text-red-400">*</span></label>
                        
                        <!-- Campo visual bloqueado -->
                        <div class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 cursor-not-allowed flex items-center">
                            <i class="fas fa-user-lock mr-3 text-slate-400"></i>
                            <div class="flex flex-col">
                                <span class="font-semibold text-sm">{{ $colabLogado->nome_completo ?? auth()->user()->name }}</span>
                                <span class="text-[10px] text-slate-300 uppercase">{{ $colabLogado->cargo ?? 'Cargo não definido' }}</span>
                            </div>
                        </div>
                        
                        <!-- Inputs ocultos para enviar os dados corretamente ao Controller -->
                        <input type="hidden" id="id_colaborador" name="colaborador_id" value="{{ $colabLogado->id ?? '' }}" data-nome="{{ $colabLogado->nome_completo ?? auth()->user()->name }}">
                        <input type="hidden" id="id_cargo_colaborador" name="cargo_colaborador" value="{{ $colabLogado->cargo ?? '' }}">
                    </div>
                @else
                    {{-- Colaborador --}}
                    <div>
                        <label class="form-label text-white font-bold">Colaborador *</label>
                        <select id="id_colaborador" name="colaborador_id" class="form-input select2-enable" required>
                            <option value="">Selecione...</option>
                            @foreach($colaboradores as $c)
                            <option value="{{ $c->id }}"
                                data-cargo="{{ $c->cargo }}"
                                {{ old('colaborador_id', $initial_values['colaborador_id'] ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->nome_exibicao }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Cargo (preenchido via AJAX) --}}
                    <div>
                        <label class="form-label text-white font-bold">Cargo</label>
                        <input type="text" id="id_cargo_colaborador" name="cargo_colaborador"
                               value="{{ old('cargo_colaborador', $initial_values['cargo'] ?? '') }}"
                               readonly class="form-input bg-slate-800/50 cursor-not-allowed text-gray-400">
                    </div>
                @endif

                {{-- Data --}}
                <div>
                    <label class="form-label text-white font-bold">Data *</label>
                    <input type="date" name="data_apontamento" id="id_data_apontamento" required
                           value="{{ old('data_apontamento', $initial_values['data_apontamento'] ?? now()->format('Y-m-d')) }}"
                           class="form-input">
                </div>

                {{-- Local de Trabalho --}}
                <div>
                    <label class="form-label text-white font-bold">Local de Trabalho *</label>
                    <select id="select-local-trabalho" name="local_execucao" class="form-input" required>
                        <option value="">Selecione...</option>
                        <option value="EXTERNO" {{ old('local_execucao', $initial_values['local_execucao'] ?? '') == 'EXTERNO' ? 'selected' : '' }}>Dentro da Obra</option>
                        <option value="INTERNO" {{ old('local_execucao', $initial_values['local_execucao'] ?? '') == 'INTERNO' ? 'selected' : '' }}>Fora da Obra</option>
                    </select>
                </div>

                {{-- Veículo --}}
                <div class="sm:col-span-2 bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="id_registrar_veiculo" name="registrar_veiculo" value="1"
                               class="w-5 h-5 accent-emerald-500 cursor-pointer"
                               {{ old('registrar_veiculo', $initial_values['registrar_veiculo'] ?? ($ultimoVeiculo ? true : false)) ? 'checked' : '' }}>
                        <label for="id_registrar_veiculo" class="font-bold text-sm cursor-pointer select-none">Adicionar veículo</label>
                    </div>
                    <div id="veiculo-container" class="{{ old('registrar_veiculo', $initial_values['registrar_veiculo'] ?? ($ultimoVeiculo ? true : false)) ? '' : 'hidden' }} mt-3 space-y-3">
                        <div>
                            <label class="form-label">Selecione o Veículo</label>
                            <select id="id_veiculo_selecao" name="veiculo_selecao" class="form-input select2-enable">
                                <option value="">Nenhum</option>
                                @foreach($veiculos as $v)
                                <option value="{{ $v->id }}" {{ old('veiculo_selecao', $initial_values['veiculo_id'] ?? $ultimoVeiculo ?? '') == $v->id ? 'selected' : '' }}>
                                    {{ $v }} {{-- __toString() --}}
                                </option>
                                @endforeach
                                <option value="OUTRO" {{ old('veiculo_selecao', $initial_values['veiculo_selecao'] ?? '') == 'OUTRO' ? 'selected' : '' }}>Outro (Manual)</option>
                            </select>
                        </div>
                        <div id="novo-veiculo-container" class="{{ old('veiculo_selecao', $initial_values['veiculo_selecao'] ?? '') == 'OUTRO' ? '' : 'hidden' }} grid grid-cols-2 gap-4 border-l-2 border-indigo-500 pl-3 mt-2 bg-slate-800 p-2 rounded-lg">
                            <div>
                                <label class="form-label text-indigo-400">Modelo (Ex: HB20) *</label>
                                <input type="text" name="veiculo_manual_modelo"
                                       value="{{ old('veiculo_manual_modelo', $initial_values['veiculo_manual_modelo'] ?? '') }}"
                                       class="form-input">
                            </div>
                            <div>
                                <label class="form-label text-indigo-400">Placa (7 dígitos) *</label>
                                <input type="text" name="veiculo_manual_placa" maxlength="7"
                                       value="{{ old('veiculo_manual_placa', $initial_values['veiculo_manual_placa'] ?? '') }}"
                                       class="form-input" style="text-transform:uppercase">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- BLOCO 2: Dados da Obra (local = EXTERNO — colaborador em campo) --}}
        {{-- ================================================================ --}}
        <div id="container-obra" class="{{ old('local_execucao', $initial_values['local_execucao'] ?? '') == 'EXTERNO' ? '' : 'hidden' }} bg-slate-900 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
            <h3 class="text-emerald-400 font-bold mb-6 text-lg uppercase tracking-wider">Dados da Obra</h3>

            <div id="inputs-obra-wrapper" class="bg-slate-800/50 p-4 rounded-xl border border-slate-700 mb-4">
                <p class="text-xs text-yellow-500 font-bold mb-3 uppercase">Código do Cliente apenas para setores sem adendo</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label class="form-label text-emerald-300">Código da Obra (com adendo)</label>
                        <select id="id_projeto" name="projeto_id" class="form-input select2-enable">
                            <option value="">Selecione...</option>
                            @foreach($projetos as $p)
                            <option value="{{ $p->id }}" {{ old('projeto_id', $initial_values['projeto_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                {{ $p->codigo }} — {{ $p->nome }}
                            </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1">Ex: R1894A00 - Vintage...</p>
                    </div>
                    <div>
                        <label class="form-label text-blue-300">Código do Cliente (Geral)</label>
                        <select id="id_codigo_cliente" name="codigo_cliente_id" class="form-input select2-enable">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cl)
                            <option value="{{ $cl->id }}" {{ old('codigo_cliente_id', $initial_values['codigo_cliente_id'] ?? '') == $cl->id ? 'selected' : '' }}>
                                {{ $cl->codigo }} — {{ $cl->nome }}
                            </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1">Ex: 1894 - Vintage...</p>
                    </div>

                    {{-- Botão de Rateio Restaurado --}}
                    <div id="container-rateio" class="hidden mt-1 pt-4 border-t border-slate-700 sm:col-span-2">
                        <div class="hidden"><input type="checkbox" name="registrar_multiplas_obras" id="id_registrar_multiplas_obras" value="1"></div> 
                        <input type="hidden" name="obras_extras_list" id="id_obras_extras_list" value="{{ old('obras_extras_list', '') }}">
                        <div id="obras-extras-wrapper" class="space-y-3 mb-1"></div>
                        <button type="button" id="btn-add-obra" class="text-xs font-bold text-indigo-400 hover:text-white transition-colors flex items-center gap-1">
                            + Add. Obra para Rateio
                        </button>
                    </div>

                </div>
            </div>
            <div id="insertion-point-obra" class="mt-6"></div>
        </div>

        {{-- ================================================================ --}}
        {{-- BLOCO 3: Fora da Obra (local = INTERNO — colaborador na base) --}}
        {{-- ================================================================ --}}
        <div id="container-fora" class="{{ old('local_execucao', $initial_values['local_execucao'] ?? '') == 'INTERNO' ? '' : 'hidden' }} bg-slate-900 rounded-xl border border-slate-800 p-6 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-orange-500"></div>
            <h3 class="text-orange-400 font-bold mb-6 text-lg uppercase tracking-wider">Fora da Obra</h3>

            <div class="mb-6">
                <label class="form-label text-orange-400 font-bold">Setor / Justificativa (Custo) *</label>
                <select id="id_centro_custo" name="centro_custo_id" class="form-input select2-enable">
                    <option value="">Selecione o setor...</option>
                    @foreach($centros_custo as $cc)
                    <option value="{{ $cc->id }}" data-permite-alocacao="{{ $cc->permite_alocacao ? 'true' : 'false' }}" {{ old('centro_custo_id', $initial_values['centro_custo_id'] ?? '') == $cc->id ? 'selected' : '' }}>
                        {{ $cc->nome }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div id="dynamic-obra-injection"></div>
        </div>

        {{-- ================================================================ --}}
        {{-- BLOCO 4: Horários + Auxiliares + Checkboxes extras --}}
        {{-- ================================================================ --}}
        <div id="common-fields-block"
             class="{{ (old('local_execucao', $initial_values['local_execucao'] ?? '')) ? '' : 'hidden' }} bg-slate-900 rounded-xl border border-slate-800 p-6 shadow-lg">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">

                {{-- Hora Início (manual) --}}
                <div id="manual-start-input" class="{{ $atividade_em_andamento ? 'hidden' : '' }}">
                    <label class="form-label">Hora Início *</label>
                    <input type="time" name="hora_inicio" id="id_hora_inicio"
                           value="{{ old('hora_inicio', $initial_values['hora_inicio'] ?? '') }}"
                           class="form-input" step="60" required>
                </div>

                {{-- Hora Término (manual) --}}
                <div id="manual-end-input" class="{{ $atividade_em_andamento ? 'hidden' : '' }}">
                    <label class="form-label">Hora Término *</label>
                    <input type="time" name="hora_termino" id="id_hora_termino"
                           value="{{ old('hora_termino', $initial_values['hora_termino'] ?? '') }}"
                           class="form-input" step="60" required>
                </div>

                {{-- Auxiliares --}}
                <div class="sm:col-span-2 bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="id_registrar_auxiliar" name="registrar_auxiliar" value="1"
                               class="w-5 h-5 accent-indigo-500 cursor-pointer"
                               {{ old('registrar_auxiliar', $initial_values['tem_auxiliar'] ?? ($ultimoAuxiliar ? true : false)) ? 'checked' : '' }}>
                        <label for="id_registrar_auxiliar" class="font-bold text-sm text-indigo-300 cursor-pointer select-none">Adicionar Auxiliares?</label>
                    </div>
                    <div class="aux-wrapper {{ old('registrar_auxiliar', $initial_values['tem_auxiliar'] ?? ($ultimoAuxiliar ? true : false)) ? '' : 'hidden' }} space-y-3 mt-3">
                        <div>
                            <label class="form-label">Auxiliar Principal</label>
                            <select id="id_auxiliar_selecao" name="auxiliar_id" class="form-input select2-enable">
                                <option value="">Nenhum</option>
                                @foreach($auxiliares as $a)
                                <option value="{{ $a->id }}" {{ old('auxiliar_id', $initial_values['auxiliar_id'] ?? $ultimoAuxiliar ?? '') == $a->id ? 'selected' : '' }}>
                                    {{ $a->nome_exibicao }} ({{ $a->cargo }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="auxiliares_extras_list" id="id_auxiliares_extras_list"
                               value="{{ old('auxiliares_extras_list', implode(',', $initial_values['auxiliares_extras'] ?? [])) }}">
                        <div id="extras-container" class="space-y-2"></div>
                        <button type="button" id="btn-add-extra"
                                class="text-xs text-indigo-400 font-bold hover:text-white transition-colors">+ Add. Auxiliar</button>
                    </div>
                </div>

                {{-- Checkboxes especiais --}}
                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-800/30 p-3 rounded-xl border border-slate-700/50">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="id_em_plantao" name="em_plantao" value="1"
                                   class="w-5 h-5 accent-purple-500 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                   @if(!(isset($podePlantao) && $podePlantao)) disabled @endif
                                   {{ old('em_plantao', $initial_values['em_plantao'] ?? false) ? 'checked' : '' }}>
                            <label for="id_em_plantao" class="text-sm text-gray-300 cursor-pointer select-none hover:text-white">Atividade em Plantão?</label>
                        </div>
                        <small id="plantao-lock-msg" class="text-slate-500 text-[10px] ml-7 items-center gap-1 {{ (isset($podePlantao) && $podePlantao) ? 'hidden' : 'flex block' }}">
                            <i class="fas fa-lock text-slate-400"></i> Disponível apenas para horários de plantão escalado (17h às 07:30h).
                        </small>
                        <p id="data-plantao-feedback" class="text-xs text-red-400 hidden ml-7">
                            Data Plantão: <span id="data-plantao-display" class="font-bold">--/--/--</span>
                        </p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="id_dorme_fora" name="dorme_fora" value="1"
                                   class="w-5 h-5 accent-blue-500 cursor-pointer"
                                   {{ old('dorme_fora', $initial_values['dorme_fora'] ?? false) ? 'checked' : '' }}>
                            <label for="id_dorme_fora" class="text-sm text-gray-300 cursor-pointer select-none hover:text-white">Dorme Fora Nesta Data?</label>
                        </div>
                        <p id="data-dorme-fora-feedback" class="text-xs text-orange-400 hidden ml-6">
                            Data Dorme-Fora: <span id="data-dorme-fora-display" class="font-bold">--/--/--</span>
                        </p>
                    </div>
                </div>

                {{-- Observações --}}
                <div class="sm:col-span-2">
                    <label class="text-xs text-gray-400 mb-1 block">Ocorrências / Obs.</label>
                    <textarea name="ocorrencias" rows="3" class="w-full bg-slate-800 border border-slate-700 rounded-md p-3 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 outline-none resize-none"
                              placeholder="Descreva ocorrências relevantes...">{{ old('ocorrencias', $initial_values['ocorrencias'] ?? '') }}</textarea>
                </div>

                {{-- Botão Check-in / Check-out --}}
                <div id="checkin-btn-area"
                     class="{{ $atividade_em_andamento ? 'sm:col-span-2' : 'hidden sm:col-span-2' }}">
                    <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700 flex flex-col items-center justify-center gap-3">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest" id="status-text">
                            @if($atividade_em_andamento)
                                ATIVIDADE INICIADA ÀS <span class="text-emerald-400 font-mono text-base">{{ $hora_inicio_em_andamento }}</span>
                            @else
                                Aguardando Registro...
                            @endif
                        </p>
                        <button type="button" id="btn-action-main"
                                class="w-full md:w-2/3 font-bold py-4 rounded-xl shadow-lg flex items-center justify-center gap-3 text-lg transition-all active:scale-95 text-white
                                       {{ $atividade_em_andamento ? 'bg-red-600 hover:bg-red-500' : 'bg-emerald-600 hover:bg-emerald-500' }}">
                            @if($atividade_em_andamento)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>REGISTRAR SAÍDA</span>
                            @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            <span>REGISTRAR ENTRADA</span>
                            @endif
                        </button>
                        <p class="text-[12px] text-gray-500">Atenção: Não é possível editar depois de iniciar.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Botão Salvar (modo manual) --}}
        <div class="flex justify-end pt-4">
            <button type="button" id="btn-pre-save"
                    class="w-full sm:w-auto px-6 py-3 sm:py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg transition-all flex justify-center items-center gap-2 {{ $atividade_em_andamento ? 'hidden' : '' }}">
                {{ $is_editing ? '💾 Salvar Alterações' : '✔ Salvar Registro' }}
            </button>
        </div>
    </form>
</div>

{{-- Modal Date Picker Customizado --}}
<div id="date-picker-modal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-900/90 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-2xl max-w-sm w-full p-4 relative z-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white font-bold" id="picker-title">Selecione a Data</h3>
                <button onclick="closeDateModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>
            <div class="flex justify-between items-center text-white mb-4">
                <button onclick="changePickerMonth(-1)" class="p-1 hover:bg-slate-700 rounded">&lt;</button>
                <span id="picker-month-label" class="font-bold uppercase text-sm"></span>
                <button onclick="changePickerMonth(1)" class="p-1 hover:bg-slate-700 rounded">&gt;</button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center mb-2">
                <div class="text-xs text-gray-500">D</div><div class="text-xs text-gray-500">S</div><div class="text-xs text-gray-500">T</div><div class="text-xs text-gray-500">Q</div><div class="text-xs text-gray-500">Q</div><div class="text-xs text-gray-500">S</div><div class="text-xs text-gray-500">S</div>
            </div>
            <div id="picker-grid" class="grid grid-cols-7 gap-1 text-sm"></div>
            <div class="mt-4 pt-4 border-t border-slate-700 flex gap-4 justify-center text-[10px]">
                <div class="flex items-center gap-1"><div class="w-2 h-2 border border-blue-500"></div><span class="text-gray-400">Hoje</span></div>
                <div class="flex items-center gap-1"><div class="w-2 h-2 border border-green-500"></div><span class="text-gray-400">Data Registro</span></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ler Notificação (Caso adicione o sino no cabeçalho ou menu) --}}
<div id="modal-ler-notificacao" class="fixed inset-0 z-[9999] hidden">
    <div class="fixed inset-0 bg-gray-900/90 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg z-10">
            <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white" id="notif-titulo">Titulo</h3>
                <button onclick="document.getElementById('modal-ler-notificacao').classList.add('hidden')" class="text-gray-400 hover:text-white text-2xl font-bold">×</button>
            </div>
            <div class="p-6">
                <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700 mb-6">
                    <p class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap" id="notif-mensagem">Mensagem...</p>
                </div>
                <form id="form-responder-notificacao" method="POST" action="">
                    @csrf
                    <label class="block text-xs font-bold text-indigo-400 uppercase mb-2">Sua Resposta / Justificativa</label>
                    <textarea name="resposta_texto" id="notif-resposta" rows="3" class="w-full bg-slate-800 border border-slate-600 rounded p-3 text-white text-sm focus:border-indigo-500 outline-none" placeholder="Escreva aqui para o gestor..."></textarea>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-6 rounded transition-colors text-sm">Enviar Resposta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Confirmação (pré-envio) --}}
<div id="confirm-modal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl">
            <div class="px-5 py-4 border-b border-slate-700 flex items-center gap-3">
                <div id="modal-icon-container" class="h-10 w-10 rounded-full bg-indigo-900/50 flex items-center justify-center"></div>
                <h3 id="modal-title" class="text-lg font-semibold text-white">Confirmação</h3>
            </div>
            <div class="px-5 py-5">
                <div id="modal-content" class="space-y-3 text-sm text-gray-300"></div>
            </div>
            <div class="px-5 py-4 border-t border-slate-700 flex flex-row-reverse gap-3">
                <button type="button" id="btn-confirm-submit"
                        class="hidden inline-flex justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl text-sm transition-all">
                    Confirmar e Enviar
                </button>
                <button type="button" id="btn-confirm-checkout"
                        class="hidden inline-flex justify-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-semibold rounded-xl text-sm transition-all">
                    Confirmar Saída
                </button>
                <button type="button" onclick="closeConfirmModal()"
                        class="inline-flex justify-center px-4 py-2 bg-slate-700 hover:bg-slate-600 text-gray-300 font-semibold rounded-xl text-sm border border-slate-600 transition-all">
                    Voltar e Editar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ========================================================================
// Configurações globais passadas pelo controller (equivalente ao Django context)
// ========================================================================
const CONFIG = {
    timerStartUrl  : "{{ route('api.timer.start') }}",
    timerStopUrl   : "{{ route('api.timer.stop') }}",
    timerStatusUrl : "{{ route('api.timer.status') }}",
    colaboradorUrl : "{{ url('/api/colaborador') }}",  // + /{id}
    centroCustoUrl : "{{ url('/api/centro-custo') }}",  // + /{id}
    saveUrl        : "{{ $is_editing ? route('apontamentos.update', $apontamento_id ?? 0) : route('apontamentos.store') }}",
    csrfToken      : "{{ csrf_token() }}",
    isEditing      : {{ $is_editing ? 'true' : 'false' }},
    atividadeEmAndamento: {{ $atividade_em_andamento ? 'true' : 'false' }},
};

// ========================================================================
// Inicialização do Select2
// ========================================================================
$(document).ready(function() {
    $('.select2-enable').select2({ theme: 'default', width: '100%' });
});

// ========================================================================
// GPS
// ========================================================================
const gpsIcon = document.getElementById('gps-status-icon');
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            document.getElementById('id_latitude').value  = pos.coords.latitude;
            document.getElementById('id_longitude').value = pos.coords.longitude;
            if(gpsIcon) {
                gpsIcon.className = 'fas fa-map-marker-alt text-emerald-800 text-sm transition-colors duration-300';
                gpsIcon.title = 'Sinal de GPS capturado com sucesso';
            }
        }, 
        (err) => {
            console.warn('Não foi possível obter localização offline. O fluxo seguirá sem as coordenadas.', err);
            document.getElementById('id_latitude').value  = '';
            document.getElementById('id_longitude').value = '';
            
            if(gpsIcon) {
                gpsIcon.className = 'fas fa-map-marker-alt text-red-800 text-sm transition-colors duration-300';
                gpsIcon.title = 'Sinal de GPS indisponível, permissão negada ou timeout';
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
}

// ========================================================================
// Toggle modo Manual vs Check-in
// ========================================================================
const modeToggle = document.getElementById('mode-toggle');
if (modeToggle) {
    function aplicarModo() {
        const isCheckin = modeToggle.checked;
        document.getElementById('id_tipo_acao').value = isCheckin ? 'CHECKIN' : 'MANUAL';
        const manualStart = document.getElementById('manual-start-input');
        const manualEnd   = document.getElementById('manual-end-input');
        const checkinArea = document.getElementById('checkin-btn-area');
        const saveBtn     = document.getElementById('btn-pre-save');

        if (manualStart) {
            manualStart.classList.toggle('hidden', isCheckin);
            document.getElementById('id_hora_inicio').required = !isCheckin;
            if (isCheckin) document.getElementById('id_hora_inicio').value = '';
        }
        if (manualEnd) {
            manualEnd.classList.toggle('hidden', isCheckin);
            document.getElementById('id_hora_termino').required = !isCheckin;
            if (isCheckin) document.getElementById('id_hora_termino').value = '';
        }
        if (checkinArea) checkinArea.classList.toggle('hidden', !isCheckin);
        if (saveBtn)     saveBtn.classList.toggle('hidden', isCheckin);
        
        // Bloquear campo de Data no Check-in
        const dateInput = document.getElementById('id_data_apontamento');
        if (dateInput) {
            if (isCheckin) {
                const now = new Date();
                const dt = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
                dateInput.value = dt;
                dateInput.readOnly = true;
                dateInput.tabIndex = -1;
                dateInput.classList.add('opacity-60', 'bg-slate-800', 'cursor-not-allowed', 'pointer-events-none');
            } else {
                dateInput.readOnly = false;
                dateInput.removeAttribute('tabindex');
                dateInput.classList.remove('opacity-60', 'bg-slate-800', 'cursor-not-allowed', 'pointer-events-none');
            }
        }
    }
    modeToggle.addEventListener('change', aplicarModo);
    document.addEventListener('DOMContentLoaded', function() {
        modeToggle.dispatchEvent(new Event('change'));
    });
}

// ========================================================================
// Variáveis e Elementos Comuns
// ========================================================================
const inputsWrapper = document.getElementById('inputs-obra-wrapper');
const injectionPoint = document.getElementById('dynamic-obra-injection');
const insertionPointObra = document.getElementById('insertion-point-obra');
const wrapperObras = document.getElementById('obras-extras-wrapper');
const hiddenObras = document.getElementById('id_obras_extras_list');
const hiddenCheckRateio = document.getElementById('id_registrar_multiplas_obras');
const btnAddObra = document.getElementById('btn-add-obra');

// ========================================================================
// Mostrar/ocultar blocos de acordo com o local de trabalho
// ========================================================================
$('#select-local-trabalho').on('change', function() {
    const val  = this.value;
    const obra = document.getElementById('container-obra');
    const fora = document.getElementById('container-fora');
    const comm = document.getElementById('common-fields-block');
    
    obra.classList.toggle('hidden', val !== 'EXTERNO');
    fora.classList.toggle('hidden', val !== 'INTERNO');
    comm.classList.toggle('hidden', !val);
    
    if (val === 'EXTERNO' && inputsWrapper && insertionPointObra) {
        insertionPointObra.parentNode.insertBefore(inputsWrapper, insertionPointObra);
    } else if (val === 'INTERNO') {
        $('#id_centro_custo').trigger('change');
    }
});
if ($('#select-local-trabalho').val()) {
    $('#select-local-trabalho').trigger('change');
}

// ========================================================================
// Projeto opcional ao selecionar CC com permite_alocacao = true (INTERNO)
// ========================================================================
$('#id_centro_custo').on('change', function() {
    if ($('#select-local-trabalho').val() !== 'INTERNO') return;
    
    const selectedOption = $(this).find('option:selected');
    const permiteAlocacao = selectedOption.data('permite-alocacao') === true || selectedOption.data('permite-alocacao') === 'true';
    
    if (permiteAlocacao) {
        if (injectionPoint && inputsWrapper) {
            injectionPoint.appendChild(inputsWrapper);
            $('#id_projeto').select2({ theme: 'default', width: '100%' });
            $('#id_codigo_cliente').select2({ theme: 'default', width: '100%' });
        }
    } else {
        if (insertionPointObra && inputsWrapper) {
            insertionPointObra.parentNode.insertBefore(inputsWrapper, insertionPointObra);
        }
        $('#id_projeto').val(null).trigger('change');
        $('#id_codigo_cliente').val(null).trigger('change');
    }
});
if ($('#id_centro_custo').val()) {
    $('#id_centro_custo').trigger('change');
}

// ========================================================================
// Preenche o cargo ao selecionar colaborador via AJAX
// ========================================================================
$('#id_colaborador').on('change', async function() {
    const cid = this.value;
    const cargoEl = document.getElementById('id_cargo_colaborador');
    if (!cid) { cargoEl.value = ''; return; }
    try {
        const resp = await fetch(`${CONFIG.colaboradorUrl}/${cid}`);
        const data = await resp.json();
        cargoEl.value = data.cargo || '';
    } catch(e) {}
});

// ========================================================================
// Veículo toggle
// ========================================================================
$('#id_registrar_veiculo').on('change', function() {
    document.getElementById('veiculo-container').classList.toggle('hidden', !this.checked);
});
$('#id_veiculo_selecao').on('change', function() {
    const isOutro = this.value === 'OUTRO';
    document.getElementById('novo-veiculo-container').classList.toggle('hidden', !isOutro);
    if (!isOutro) {
        document.querySelector('input[name="veiculo_manual_modelo"]').value = '';
        document.querySelector('input[name="veiculo_manual_placa"]').value = '';
    }
});

// ========================================================================
// Limpar Obra/Cliente mutuamente
// ========================================================================
$('#id_projeto').on('change', function() {
    if ($(this).val()) $('#id_codigo_cliente').val(null).trigger('change.select2');
});
$('#id_codigo_cliente').on('change', function() {
    if ($(this).val()) $('#id_projeto').val(null).trigger('change.select2');
});

// ========================================================================
// RATEIO LÓGICA
// ========================================================================
function updateHybridHidden() {
    if (!wrapperObras) return;
    const rows = wrapperObras.querySelectorAll('div.flex-row');
    const vals = Array.from(rows).map(row => {
        const type = row.querySelector('select:first-child').value;
        const id = $(row.querySelector('.input-rateio-target')).val();
        return id ? `${type}_${id}` : null;
    }).filter(v => v);
    
    if (hiddenObras) hiddenObras.value = vals.join(',');
    if (hiddenCheckRateio) hiddenCheckRateio.checked = vals.length > 0;
}

function restoreRateioRow(type, id) {
    if (!wrapperObras) return;

    const div = document.createElement('div');
    div.className = "flex flex-row items-center gap-2 bg-slate-800/50 p-2 rounded border border-slate-700 mb-2 w-full animate-fadeIn";

    const typeSel = document.createElement('select');
    typeSel.className = "bg-slate-700 border border-slate-600 text-white text-sm rounded focus:ring-indigo-500 focus:border-indigo-500 block w-24 h-[42px] p-1 flex-shrink-0 cursor-pointer";
    typeSel.innerHTML = '<option value="P">OBRA</option><option value="C">CLIENTE</option>';
    typeSel.value = type;

    const selectCont = document.createElement('div');
    selectCont.className = "flex-1 min-w-0";
    const newSel = document.createElement('select');
    newSel.className = "form-control w-full input-rateio-target";
    selectCont.appendChild(newSel);

    const btnRem = document.createElement('button');
    btnRem.type = "button"; 
    btnRem.innerHTML = "&times;";
    btnRem.className = "text-red-500 hover:text-red-400 transition-colors h-[42px] w-10 flex items-center justify-center text-2xl flex-shrink-0";

    div.append(typeSel, selectCont, btnRem);
    wrapperObras.appendChild(div);

    const loadOptions = () => {
        const isProj = typeSel.value === 'P';
        const original = isProj ? document.getElementById('id_projeto') : document.getElementById('id_codigo_cliente');
        
        $(newSel).empty().select2({
            theme: 'default',
            data: $(original).find('option').map(function() { return {id: $(this).val(), text: $(this).text()}; }).get(),
            placeholder: isProj ? "Selecione a Obra..." : "Selecione o Cliente...",
            width: '100%' 
        }).on('change', updateHybridHidden);
    };

    $(typeSel).on('change', function() {
        loadOptions();
        $(newSel).val(null).trigger('change');
    });
    loadOptions();
    $(newSel).val(id).trigger('change');

    btnRem.onclick = () => { $(newSel).select2('destroy'); div.remove(); updateHybridHidden(); };
}

if (hiddenObras && hiddenObras.value) {
    const items = hiddenObras.value.split(',');
    items.forEach(item => {
        if(item.includes('_')) {
            const [type, id] = item.split('_');
            if(type && id) restoreRateioRow(type, id);
        }
    });
    if(hiddenCheckRateio) hiddenCheckRateio.checked = true;
}

if (btnAddObra) {
    btnAddObra.addEventListener('click', function() {
        if (!wrapperObras) return;
        const total = wrapperObras.children.length;
        if (total >= 9) return alert("Limite de 10 atingido.");

        const div = document.createElement('div');
        div.className = "flex flex-row items-center gap-2 bg-slate-800/50 p-2 rounded border border-slate-700 mb-2 w-full animate-fadeIn";

        const typeSel = document.createElement('select');
        typeSel.className = "bg-slate-700 border border-slate-600 text-white text-sm rounded focus:ring-indigo-500 focus:border-indigo-500 block w-24 h-[42px] p-1 flex-shrink-0 cursor-pointer";
        typeSel.innerHTML = '<option value="P">OBRA</option><option value="C">CLIENTE</option>';

        const selectCont = document.createElement('div');
        selectCont.className = "flex-1 min-w-0";
        const newSel = document.createElement('select');
        newSel.className = "form-control w-full input-rateio-target";
        selectCont.appendChild(newSel);

        const btnRem = document.createElement('button');
        btnRem.type = "button"; 
        btnRem.innerHTML = "&times;";
        btnRem.className = "text-red-500 hover:text-red-400 transition-colors h-[42px] w-10 flex items-center justify-center text-2xl flex-shrink-0";

        div.append(typeSel, selectCont, btnRem);
        wrapperObras.appendChild(div);

        const loadOptions = () => {
            const isProj = typeSel.value === 'P';
            const original = isProj ? document.getElementById('id_projeto') : document.getElementById('id_codigo_cliente');
            
            $(newSel).empty().select2({
                theme: 'default',
                data: $(original).find('option').map(function() { return {id: $(this).val(), text: $(this).text()}; }).get(),
                placeholder: isProj ? "Selecione a Obra..." : "Selecione o Cliente...",
                width: '100%' 
            }).on('change', updateHybridHidden);
        };

        $(typeSel).on('change', loadOptions);
        loadOptions();

        btnRem.onclick = () => { $(newSel).select2('destroy'); div.remove(); updateHybridHidden(); };
        
        if (hiddenCheckRateio) hiddenCheckRateio.checked = true;
    });
}

// ========================================================================
// Auxiliar toggle e Lógica de Auxiliares Extras
// ========================================================================
document.getElementById('id_registrar_auxiliar').addEventListener('change', function() {
    document.querySelector('.aux-wrapper').classList.toggle('hidden', !this.checked);
});

const containerExtras = document.getElementById('extras-container');
const hiddenAuxExtras = document.getElementById('id_auxiliares_extras_list');
const btnAddExtra = document.getElementById('btn-add-extra');

function updateAuxExtrasHidden() {
    if (!containerExtras) return;
    const selects = containerExtras.querySelectorAll('select.input-aux-extra');
    const vals = Array.from(selects).map(s => $(s).val()).filter(v => v);
    if (hiddenAuxExtras) hiddenAuxExtras.value = vals.join(',');
}

function restoreAuxExtraRow(id) {
    if (!containerExtras) return;
    const div = document.createElement('div');
    div.className = "flex flex-row items-center gap-2 bg-slate-800/50 p-2 rounded border border-slate-700 w-full animate-fadeIn";

    const selectCont = document.createElement('div');
    selectCont.className = "flex-1 min-w-0";
    const newSel = document.createElement('select');
    newSel.className = "form-control w-full input-aux-extra";
    newSel.name = "auxiliares[]";
    selectCont.appendChild(newSel);

    const btnRem = document.createElement('button');
    btnRem.type = "button";
    btnRem.innerHTML = "&times;";
    btnRem.className = "text-red-500 hover:text-red-400 transition-colors h-[42px] w-10 flex items-center justify-center text-2xl flex-shrink-0";

    div.append(selectCont, btnRem);
    containerExtras.appendChild(div);

    const original = document.getElementById('id_auxiliar_selecao');
    $(newSel).empty().select2({
        theme: 'default',
        data: $(original).find('option').map(function() { return {id: $(this).val(), text: $(this).text()}; }).get(),
        placeholder: "Selecione o Auxiliar...",
        width: '100%'
    }).on('change', updateAuxExtrasHidden);
    
    $(newSel).val(id).trigger('change');

    btnRem.onclick = () => { $(newSel).select2('destroy'); div.remove(); updateAuxExtrasHidden(); };
}

if (hiddenAuxExtras && hiddenAuxExtras.value) {
    const items = hiddenAuxExtras.value.split(',');
    items.forEach(id => {
        if(id) restoreAuxExtraRow(id);
    });
}

if (btnAddExtra) {
    btnAddExtra.addEventListener('click', function() {
        if (!containerExtras) return;
        const total = containerExtras.children.length;
        if (total >= 5) return alert("Limite de 5 auxiliares extras atingido.");

        const div = document.createElement('div');
        div.className = "flex flex-row items-center gap-2 bg-slate-800/50 p-2 rounded border border-slate-700 w-full animate-fadeIn mt-2";

        const selectCont = document.createElement('div');
        selectCont.className = "flex-1 min-w-0";
        const newSel = document.createElement('select');
        newSel.className = "form-control w-full input-aux-extra";
        newSel.name = "auxiliares[]";
        selectCont.appendChild(newSel);

        const btnRem = document.createElement('button');
        btnRem.type = "button";
        btnRem.innerHTML = "&times;";
        btnRem.className = "text-red-500 hover:text-red-400 transition-colors h-[42px] w-10 flex items-center justify-center text-2xl flex-shrink-0";

        div.append(selectCont, btnRem);
        containerExtras.appendChild(div);

        const original = document.getElementById('id_auxiliar_selecao');
        $(newSel).empty().select2({
            theme: 'default',
            data: $(original).find('option').map(function() { return {id: $(this).val(), text: $(this).text()}; }).get(),
            placeholder: "Selecione o Auxiliar...",
            width: '100%'
        }).on('change', updateAuxExtrasHidden);
        
        $(newSel).val(null).trigger('change');

        btnRem.onclick = () => { $(newSel).select2('destroy'); div.remove(); updateAuxExtrasHidden(); };
    });
}

// ========================================================================
// Check-in / Check-out (botão principal)
// ========================================================================
const btnMain = document.getElementById('btn-action-main');
if (btnMain) {
    btnMain.addEventListener('click', async function(e) {
        const form = document.getElementById('apontamentoForm');
        
        // Remove quaisquer hidden inputs de auxiliares criados anteriormente (evita duplicação em re-submits)
        form.querySelectorAll('input.__aux_hidden_sync').forEach(el => el.remove());

        // Percorre todos os selects dinâmicos de auxiliares extras e cria hidden inputs
        const extrasContainer = document.getElementById('extras-container');
        if (extrasContainer) {
            const selects = extrasContainer.querySelectorAll('select.input-aux-extra');
            selects.forEach(sel => {
                const val = $(sel).val(); // Lê o valor via Select2 API
                if (val) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'auxiliares[]';
                    hidden.value = val;
                    hidden.className = '__aux_hidden_sync';
                    form.appendChild(hidden);
                }
            });
        }

        // Validação nativa antes do check-in
        if (!form.reportValidity()) {
            return;
        }

        const emAndamento = CONFIG.atividadeEmAndamento;
        if (emAndamento) {
            // ── CHECKOUT ──
            openConfirmModal(
                'Confirmar Saída',
                '<p>Confirma o <strong class="text-red-400">Registro de Saída</strong> agora?</p>',
                null, 'checkout'
            );
        } else {
            // ── CHECKIN — Injeta Hora Real antes de montar payload ──
            const now = new Date();
            const hs = String(now.getHours()).padStart(2, '0');
            const ms = String(now.getMinutes()).padStart(2, '0');
            const dt = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            
            const hi = document.getElementById('id_hora_inicio');
            if (hi) hi.value = `${hs}:${ms}`;
            const da = document.getElementById('id_data_apontamento');
            if (da) da.value = dt;

            // Monta payload a partir do formulário atualizado
            const payload = new FormData(form);
            payload.set('tipo_acao', 'START'); // Informa o Request que é check-in

            // --- INÍCIO DA BLINDAGEM OFFLINE PARA O CHECK-IN ---
            if (!navigator.onLine) {
                if (typeof parseFormData === 'function' && typeof salvarOffline === 'function') {
                    const dados = parseFormData(payload);
                    dados._tipo_offline = 'checkin';
                    salvarOffline(dados, CONFIG.timerStartUrl, 'POST');
                    exibirToast("Check-in salvo offline. Sincronizará quando a rede voltar.", 'aviso');
                    setTimeout(() => window.location.href = '/historico', 1500);
                } else {
                    exibirToast("Erro: Módulo offline não carregado.", 'erro');
                }
                return;
            }
            // --- FIM DA BLINDAGEM OFFLINE ---

            try {
                const resp = await fetch(CONFIG.timerStartUrl, { 
                    method: 'POST', 
                    body: payload,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                
                if (resp.ok && data.success) {
                    exibirToast(`Entrada registrada às ${data.inicio}!`, 'sucesso');
                    setTimeout(() => location.reload(), 1500);
                } else if (resp.status === 422 && data.errors) {
                    // Erros de validação do Laravel padronizados no modal premium
                    let msgsArray = [];
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        msgsArray.push(msgs[0]);
                    }
                    exibirModalErroValidacao(msgsArray, "Pendências no Check-in");
                } else if (!resp.ok) {
                    // Outros erros genéricos
                    exibirModalErroValidacao([data.error || data.message || "Ocorreu um erro ao processar a solicitação."], "Erro do Sistema");
                } else {
                    exibirToast(data.error || data.message || 'Erro ao iniciar.', 'erro');
                }
            } catch (e) {
                // Caiu aqui = falha na rede (net::ERR_INTERNET_DISCONNECTED) ou timeout forçado
                console.warn("Falha de comunicação no Check-in, jogando para a fila offline. Erro:", e);
                if (typeof parseFormData === 'function' && typeof salvarOffline === 'function') {
                    const dados = parseFormData(payload);
                    dados._tipo_offline = 'checkin';
                    salvarOffline(dados, CONFIG.timerStartUrl, 'POST');
                    exibirToast("Conexão instável. Check-in salvo offline e sincronizará depois.", 'aviso');
                    setTimeout(() => window.location.href = '/historico', 1500);
                } else {
                    exibirToast('Erro de conexão.', 'erro');
                }
            }
        }
    });
}

// Confirmação de checkout
document.getElementById('btn-confirm-checkout')?.addEventListener('click', async function() {
    closeConfirmModal();
    try {
        const resp = await fetch(CONFIG.timerStopUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrfToken },
            body: JSON.stringify({})
        });
        const data = await resp.json();
        if (data.success) {
            exibirToast('Saída registrada com sucesso!', 'sucesso');
            setTimeout(() => location.href = "{{ route('historico.index') }}", 1800);
        } else {
            exibirToast(data.error || 'Erro ao encerrar.', 'erro');
        }
    } catch(e) {
        exibirToast('Erro de conexão.', 'erro');
    }
});

// ========================================================================
// LÓGICA DO DATE PICKER CUSTOMIZADO (RESTAURADA DO LEGADO)
// ========================================================================
let activeDateInputId = null;
let pickerDate = new Date();
const pickerModal = document.getElementById('date-picker-modal');
const pickerGrid = document.getElementById('picker-grid');
const pickerLabel = document.getElementById('picker-month-label');

const plantaoCheck = document.getElementById('id_em_plantao');
const dormeCheck = document.getElementById('id_dorme_fora');

if(plantaoCheck) {
    plantaoCheck.addEventListener('change', function() {
        if (this.checked) openDateSelector('id_data_plantao', 'Selecione a Data do Plantão');
        else { 
            if(document.getElementById('id_data_plantao')) document.getElementById('id_data_plantao').value = ''; 
            document.getElementById('data-plantao-feedback').classList.add('hidden'); 
        }
    });
}

if(dormeCheck) {
    dormeCheck.addEventListener('change', function() {
        if (this.checked) openDateSelector('id_data_dorme_fora', 'Selecione a Data do Dorme-Fora');
        else { 
            if(document.getElementById('id_data_dorme_fora')) document.getElementById('id_data_dorme_fora').value = ''; 
            document.getElementById('data-dorme-fora-feedback').classList.add('hidden'); 
        }
    });
}

// Interceptar o input de data principal nativo
document.getElementById('id_data_apontamento').addEventListener('click', function(e) {
    e.preventDefault();
    this.blur(); // Remove o teclado móvel do celular ao clicar
    openDateSelector('id_data_apontamento', 'Selecione a Data do Registro');
});

window.openDateSelector = function(inputId, title = "Selecione uma Data") {
    if (inputId === 'id_data_plantao' || inputId === 'id_data_dorme_fora') {
        const mainDateVal = document.getElementById('id_data_apontamento').value;
        if (!mainDateVal) { alert("Preencha a Data do Registro (Início) primeiro."); return; }
        const parts = mainDateVal.split('-'); // Padrão Y-m-d Laravel
        pickerDate = new Date(parts[0], parts[1] - 1, parts[2]);
    } else {
        const currVal = document.getElementById(inputId).value;
        if (currVal && currVal.includes('-')) {
            const parts = currVal.split('-');
            pickerDate = new Date(parts[0], parts[1] - 1, parts[2]);
        } else { pickerDate = new Date(); }
    }
    activeDateInputId = inputId;
    document.getElementById('picker-title').textContent = title;
    pickerModal.classList.remove('hidden');
    renderPicker();
}

window.closeDateModal = function() { 
    pickerModal.classList.add('hidden'); 
    if (activeDateInputId === 'id_data_plantao' && !document.getElementById('id_data_plantao').value) plantaoCheck.checked = false;
    if (activeDateInputId === 'id_data_dorme_fora' && !document.getElementById('id_data_dorme_fora').value) dormeCheck.checked = false;
    activeDateInputId = null;
}

window.changePickerMonth = function(d) { pickerDate.setMonth(pickerDate.getMonth() + d); renderPicker(); }

function renderPicker() {
    pickerGrid.innerHTML = '';
    const m = pickerDate.getMonth();
    const y = pickerDate.getFullYear();
    pickerLabel.textContent = pickerDate.toLocaleString('pt-BR', { month: 'long', year: 'numeric' });
    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const today = new Date();
    const dataPrincipalStr = document.getElementById('id_data_apontamento').value;
    const isRestrictedMode = (activeDateInputId === 'id_data_plantao' || activeDateInputId === 'id_data_dorme_fora');

    for(let i=0; i<firstDay; i++) pickerGrid.appendChild(document.createElement('div'));
    for(let d=1; d<=daysInMonth; d++) {
        const el = document.createElement('div');
        const currentDate = new Date(y, m, d);
        const isToday = currentDate.getDate() === today.getDate() && currentDate.getMonth() === today.getMonth() && currentDate.getFullYear() === today.getFullYear();
        
        // Mantém formato Y-m-d para compatibilidade nativa com Laravel Controller
        const formatBackend = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        // Formato brasileiro apenas para vizualição na UI
        const formatDisplay = `${String(d).padStart(2,'0')}/${String(m+1).padStart(2,'0')}/${y}`;
        
        el.className = "h-8 w-8 flex items-center justify-center rounded transition text-sm border border-transparent";
        el.textContent = d;
        let isClickable = true;

        if (isRestrictedMode) {
            if (formatBackend === dataPrincipalStr) {
                el.classList.add('bg-emerald-600', 'text-white', 'font-bold', 'cursor-pointer', 'shadow-md');
            } else {
                isClickable = false;
                el.classList.add('cursor-not-allowed');
                if (isToday) el.classList.add('border-blue-500', 'text-blue-400', 'font-bold', 'opacity-60');
                else el.classList.add('text-gray-600', 'opacity-20');
            }
        } else {
            el.classList.add('cursor-pointer', 'hover:bg-slate-700');
            const currentInputVal = document.getElementById(activeDateInputId).value;
            if (currentInputVal === formatBackend) el.classList.add('bg-emerald-600', 'text-white', 'font-bold', 'shadow-md');
            else if (isToday) el.classList.add('border-blue-500', 'text-blue-400', 'font-bold');
            else el.classList.add('text-gray-300');
        }

        if (isClickable) {
            el.onclick = function() {
                if (activeDateInputId) {
                    const inputTarget = document.getElementById(activeDateInputId);
                    inputTarget.value = formatBackend;
                    inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    if (activeDateInputId === 'id_data_plantao') { 
                        document.getElementById('data-plantao-display').textContent = formatDisplay; 
                        document.getElementById('data-plantao-feedback').classList.remove('hidden'); 
                    }
                    if (activeDateInputId === 'id_data_dorme_fora') { 
                        document.getElementById('data-dorme-fora-display').textContent = formatDisplay; 
                        document.getElementById('data-dorme-fora-feedback').classList.remove('hidden'); 
                    }
                    
                    if (activeDateInputId === 'id_data_apontamento') {
                        const pInput = document.getElementById('id_data_plantao');
                        const dInput = document.getElementById('id_data_dorme_fora');
                        // Reset condicional
                        if(pInput && pInput.value && pInput.value !== formatBackend) { pInput.value = ''; document.getElementById('data-plantao-feedback').classList.add('hidden'); }
                        if(dInput && dInput.value && dInput.value !== formatBackend) { dInput.value = ''; document.getElementById('data-dorme-fora-feedback').classList.add('hidden'); }
                    }
                }
                pickerModal.classList.add('hidden'); activeDateInputId = null;
            };
        }
        pickerGrid.appendChild(el);
    }
}

// ========================================================================
// CONFIRM MODAL (VALIDAÇÕES ROBUSTAS DO DJANGO RESTAURADAS)
// ========================================================================
document.getElementById('btn-pre-save')?.addEventListener('click', function(e) {
    console.log('Botão Salvar clicado');
    const form = document.getElementById('apontamentoForm');
    
    // Garante que campos invisíveis não tenham required travando o submit silenciosamente
    form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.offsetParent === null && el.required) {
            console.log('Removendo required de campo oculto: ', el.id || el.name);
            el.required = false;
        }
    });

    // Validação nativa antes de prosseguir
    if (!form.reportValidity()) {
        console.log('Validação nativa falhou. Verifique os tooltips do navegador.');
        return;
    }

    // Helper para extração segura de dados do Select2 ou DOM nativo
    const getSelectText = (id) => {
        const el = $(id);
        if (el.length === 0) return "";
        if (el.is('select')) {
            try {
                const selectData = el.select2('data');
                if (selectData && selectData.length > 0) return selectData[0].text;
            } catch (e) {
                // Fallback caso o select2 não esteja devidamente inicializado
            }
            return el.find('option:selected').text() || "";
        }
        // Fallback para inputs hidden (ex: Modo Operacional)
        return el.attr('data-nome') || el.val() || "";
    };

    const data = {
        colab: getSelectText('#id_colaborador'),
        colab_id: $('#id_colaborador').val(),
        data_pt: document.getElementById('id_data_apontamento').value, 
        local: document.getElementById('select-local-trabalho').value,
        obra: getSelectText('#id_projeto'),
        obra_id: $('#id_projeto').val(),
        cliente_txt: getSelectText('#id_codigo_cliente'),
        cliente_id: $('#id_codigo_cliente').val(),
        centro_custo_txt: getSelectText('#id_centro_custo'),
        centro_custo_id: $('#id_centro_custo').val(),
        inicio: document.getElementById('id_hora_inicio').value,
        fim: document.getElementById('id_hora_termino').value,
        usou_veiculo: document.getElementById('id_registrar_veiculo').checked,
        em_plantao: document.getElementById('id_em_plantao').checked,
        data_plantao: document.getElementById('id_data_plantao')?.value,
        dorme_fora: document.getElementById('id_dorme_fora').checked,
        data_dorme_fora: document.getElementById('id_data_dorme_fora')?.value
    };

    let errors = [];

    // --- VALIDAÇÕES ---
    if(!data.colab_id) errors.push("Colaborador é obrigatório.");
    if(!data.data_pt) errors.push("Data é obrigatória.");
    if(!data.inicio) errors.push("Hora de Início é obrigatória.");
    if(!data.fim) errors.push("Hora de Término é obrigatória.");

    if(data.local === 'EXTERNO') {
        if (!data.obra_id && !data.cliente_id) errors.push("Selecione o Código da Obra OU o Código do Cliente.");
        if (data.obra_id && data.cliente_id) errors.push("Selecione APENAS UM (Obra ou Cliente), não ambos.");
    } else {
        if(!data.centro_custo_id) errors.push("O campo 'Setor / Justificativa (Custo)' é obrigatório.");
        const injectionPoint = document.getElementById('dynamic-obra-injection');
        const wrapper = document.getElementById('inputs-obra-wrapper');
        const inputsObraVisiveis = injectionPoint && wrapper && injectionPoint.contains(wrapper);
        if (inputsObraVisiveis) {
            if (!data.obra_id && !data.cliente_id) errors.push("Para este Centro de Custo, é necessário informar a Obra ou Cliente.");
            if (data.obra_id && data.cliente_id) errors.push("Selecione apenas um destino (Obra ou Cliente).");
        }
    }

    if(data.em_plantao && !data.data_plantao) errors.push("Selecione a data do Plantão no ícone do calendário.");
    if(data.dorme_fora && !data.data_dorme_fora) errors.push("Selecione a data do Dorme-Fora no calendário.");

    if (data.data_pt && data.inicio && data.fim) {
        const parts = data.data_pt.split('-');
        const dtStart = new Date(parts[0], parts[1]-1, parts[2], ...data.inicio.split(':'));
        const dtEnd = new Date(parts[0], parts[1]-1, parts[2], ...data.fim.split(':'));
        if (dtEnd < dtStart) dtEnd.setDate(dtEnd.getDate() + 1); // Trata virada de noite
        if (dtStart > new Date()) errors.push("O horário de início não pode ser no futuro.");
        if (dtEnd > new Date()) errors.push("O horário de término não pode ser no futuro.");
    }

    // --- EXIBIÇÃO NO MODAL ---
    const iconContainer = document.getElementById('modal-icon-container');
    const modalTitle = document.getElementById('modal-title');
    
    if(errors.length > 0) {
        exibirModalErroValidacao(errors, "Dados Incompletos");
    } else {
        iconContainer.className = "flex h-10 w-10 items-center justify-center rounded-full bg-emerald-900/50 flex-shrink-0";
        iconContainer.innerHTML = `<svg class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`;
        modalTitle.className = "text-lg font-semibold text-emerald-400";

        const displayData = data.data_pt.split('-').reverse().join('/'); // Exibição limpa em pt-BR
        let contextInfo = "";
        
        if(data.local === 'EXTERNO') {
            if(data.obra_id) contextInfo = `<span class="text-indigo-300 font-bold">Obra:</span> ${data.obra}`;
            else if(data.cliente_id) contextInfo = `<span class="text-blue-300 font-bold">Cliente Geral:</span> ${data.cliente_txt}`;
        } else {
            contextInfo = `<span class="text-orange-300 font-bold">Centro Custo:</span> ${data.centro_custo_txt}`;
            if(data.obra_id) contextInfo += `<br><span class="text-indigo-300 font-bold text-xs pl-2">↳ Alocado na(s) Obra(s):</span> ${data.obra}`;
        }
        
        let extrasInfo = "";
        if(data.em_plantao) extrasInfo += `<span class="bg-red-900/50 text-red-300 px-2 py-0.5 rounded text-xs border border-red-800">PLANTÃO</span> `;
        if(data.dorme_fora) extrasInfo += `<span class="bg-purple-900/50 text-purple-300 px-2 py-0.5 rounded text-xs border border-purple-800">DORME-FORA</span>`;
        
        let html = `<div class="bg-slate-900 p-4 rounded-lg border border-slate-700 space-y-2 text-sm">
            <p><span class="text-gray-500">Colaborador:</span> <span class="text-white font-bold text-lg block">${data.colab}</span></p>
            <p><span class="text-gray-500">Data:</span> <span class="text-white font-bold">${displayData}</span></p>
            <div class="py-2 border-y border-slate-700 my-2 space-y-1"><p>${contextInfo}</p></div>
            <p><span class="text-gray-500">Horário:</span> <span class="text-emerald-400 font-mono font-bold text-base">${data.inicio}</span> até <span class="text-red-400 font-mono font-bold text-base">${data.fim}</span></p>
            <div class="mt-2">${extrasInfo}</div>
            </div>`;
        
        openConfirmModal("Confirmação de Envio", html, 'submit');
    }
});

document.getElementById('btn-confirm-submit')?.addEventListener('click', function() {
    closeConfirmModal();

    // ── BLINDAGEM: Garante que os valores dos auxiliares extras são enviados como array ──
    const form = document.getElementById('apontamentoForm');

    // Remove quaisquer hidden inputs de auxiliares criados anteriormente (evita duplicação em re-submits)
    form.querySelectorAll('input.__aux_hidden_sync').forEach(el => el.remove());

    // Percorre todos os selects dinâmicos de auxiliares extras e cria hidden inputs
    const extrasContainer = document.getElementById('extras-container');
    if (extrasContainer) {
        const selects = extrasContainer.querySelectorAll('select.input-aux-extra');
        selects.forEach(sel => {
            const val = $(sel).val(); // Lê o valor via Select2 API (fonte de verdade)
            if (val) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'auxiliares[]';
                hidden.value = val;
                hidden.className = '__aux_hidden_sync';
                form.appendChild(hidden);
            }
            // Remove o name do select original para evitar duplicação
            sel.removeAttribute('name');
        });
    }

    // Usamos requestSubmit() ao invés de submit() para disparar o evento 'submit'
    // permitindo que o nosso offline-sync.js intercepte a chamada (preventDefault).
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }
});

// Abertura do Modal de Notificações
window.abrirNotificacaoModal = function(id, titulo, mensagem) {
    document.getElementById('notif-titulo').innerText = titulo;
    document.getElementById('notif-mensagem').innerText = mensagem;
    document.getElementById('modal-ler-notificacao').classList.remove('hidden');
}

// ========================================================================
// Helpers modais e toasts
// ========================================================================
function exibirModalErroValidacao(errorsArray, titulo = "Dados Incompletos") {
    const iconContainer = document.getElementById('modal-icon-container');
    const modalTitle = document.getElementById('modal-title');
    
    if (iconContainer) {
        iconContainer.className = "flex h-10 w-10 items-center justify-center rounded-full bg-red-900/50 flex-shrink-0";
        iconContainer.innerHTML = `<svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
    }
    if (modalTitle) {
        modalTitle.className = "text-lg font-semibold text-red-400";
    }
    
    let errorHtml = `<p class="font-bold text-red-300 mb-2">Correções necessárias:</p><ul class="list-disc list-inside space-y-1 text-gray-400">`;
    errorsArray.forEach(e => errorHtml += `<li>${e}</li>`);
    errorHtml += `</ul>`;
    
    openConfirmModal(titulo, errorHtml, null);
    document.getElementById('btn-confirm-submit')?.classList.add('hidden');
    document.getElementById('btn-confirm-checkout')?.classList.add('hidden');
}

function openConfirmModal(titulo, conteudo, tipo = 'submit', subTipo = null) {
    document.getElementById('modal-title').textContent = titulo;
    document.getElementById('modal-content').innerHTML = conteudo;
    const btnSub  = document.getElementById('btn-confirm-submit');
    const btnCo   = document.getElementById('btn-confirm-checkout');
    btnSub.classList.add('hidden');
    btnCo.classList.add('hidden');
    if (subTipo === 'checkout') btnCo.classList.remove('hidden');
    else                        btnSub.classList.remove('hidden');
    document.getElementById('confirm-modal').classList.remove('hidden');
}
function closeConfirmModal() {
    document.getElementById('confirm-modal').classList.add('hidden');
}

function exibirToast(mensagem, tipo = 'sucesso') {
    const container = document.getElementById('toast-container');
    const cls = tipo === 'erro'
        ? 'bg-red-50 border-red-500 text-red-800'
        : 'bg-emerald-50 border-emerald-500 text-emerald-800';
    const div = document.createElement('div');
    div.className = `flex items-start gap-3 p-4 text-sm ${cls} rounded-lg border shadow-lg animate-fadeIn`;
    div.innerHTML = `<span class="font-medium">${tipo === 'erro' ? 'Erro!' : 'Sucesso!'}</span> ${mensagem}
        <button onclick="this.parentElement.remove()" class="ml-auto font-bold opacity-60 hover:opacity-100">✕</button>`;
    container.appendChild(div);
    setTimeout(() => div.remove(), 6000);
}

// ========================================================================
// Remove loader após carregar e sincronizar status do timer
// ========================================================================
async function syncTimerStatus() {
    try {
        const resp = await fetch(CONFIG.timerStatusUrl);
        const data = await resp.json();
        if (data.ativo && !CONFIG.isEditing) {
            CONFIG.atividadeEmAndamento = true;
            const btnMain = document.getElementById('btn-action-main');
            if (btnMain) {
                btnMain.classList.remove('bg-emerald-600', 'hover:bg-emerald-500');
                btnMain.classList.add('bg-red-600', 'hover:bg-red-500');
                btnMain.querySelector('span').textContent = 'REGISTRAR SAÍDA';
            }
            const statusText = document.getElementById('status-text');
            if (statusText) statusText.innerHTML = `ATIVIDADE INICIADA ÀS <span class="text-emerald-400 font-mono text-base">${data.inicio_str}</span>`;
        }
    } catch(e) {}
}

// ========================================================================
// Blindagem do Formulário (Trava campos se Check-in Ativo)
// ========================================================================
function bloquearCamposEmExecucao() {
    if (!CONFIG.atividadeEmAndamento || CONFIG.isEditing) return;
    
    const form = document.getElementById('apontamentoForm');
    if (!form) return;
    
    // Pega todos os inputs, selects, textareas e botões
    const elementos = form.querySelectorAll('input, select, textarea, button');
    
    elementos.forEach(el => {
        // Exceções: NÃO bloquear inputs hidden, o botão principal de check-out, nem os botões do modal de confirmação
        if (
            el.type !== 'hidden' && 
            el.id !== 'btn-action-main' && 
            !el.closest('#confirm-modal')
        ) {
            el.disabled = true;
            el.classList.add('cursor-not-allowed', 'opacity-60', 'bg-slate-800');
        }
    });
    
    // Bloquear também o Toggle de Modos para não permitir mudanças visuais
    const toggle = document.getElementById('mode-toggle');
    if (toggle) {
        toggle.disabled = true;
        toggle.classList.add('cursor-not-allowed');
        const label = toggle.closest('label');
        if (label) {
            label.style.pointerEvents = 'none';
            label.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

window.addEventListener('load', async function() {
    await syncTimerStatus();
    const loader = document.getElementById('page-loader');
    loader.style.opacity = '0';
    setTimeout(() => loader.style.display = 'none', 500);
    bloquearCamposEmExecucao();
});

// ========================================================================
// Controle Dinâmico do Rateio de Obras
// ========================================================================
const selectLocalTrabalho = document.getElementById('select-local-trabalho');
if (selectLocalTrabalho) {
    selectLocalTrabalho.addEventListener('change', function() {
        const containerRateio = document.getElementById('container-rateio');
        if (containerRateio) {
            const inputs = containerRateio.querySelectorAll('input, select, textarea');
            if (this.value === 'INTERNO') {
                containerRateio.classList.remove('hidden');
                inputs.forEach(input => input.disabled = false);
            } else {
                containerRateio.classList.add('hidden');
                inputs.forEach(input => input.disabled = true);
            }
        }
    });
    // Disparar no carregamento inicial para estado inicial correto
    selectLocalTrabalho.dispatchEvent(new Event('change'));
}

// ========================================================================
// Funções do Modal de Timeline
// ========================================================================
function openTimelineModal() {
    document.getElementById('modal-timeline').classList.remove('hidden');
    
    // Lógica para forçar Landscape no Mobile
    if (window.innerWidth <= 768) {
        try {
            // Navegadores mobile geralmente exigem Fullscreen para permitir o bloqueio de orientação
            let elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen().then(() => {
                    if (screen.orientation && screen.orientation.lock) {
                        screen.orientation.lock('landscape').catch(err => console.log("Rotação bloqueada pelo navegador:", err));
                    }
                }).catch(err => console.log("Erro ao entrar em fullscreen:", err));
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
                if (screen.orientation && screen.orientation.lock) {
                    screen.orientation.lock('landscape');
                }
            }
        } catch (e) {
            console.log("Screen Orientation API não suportada neste navegador.");
        }
    }
}

function closeTimelineModal() {
    document.getElementById('modal-timeline').classList.add('hidden');
    
    // Lógica para fechar Fullscreen e destravar orientação
    if (document.fullscreenElement) {
        document.exitFullscreen().then(() => {
            if (screen.orientation && screen.orientation.unlock) {
                screen.orientation.unlock();
            }
        }).catch(err => console.log(err));
    }
}

// ========================================================================
// Regra de Obrigatoriedade de Veículo
// ========================================================================
function aplicarRegraVeiculoObrigatorio() {
    const localSelect = document.getElementById('select-local-trabalho');
    const setorSelect = document.getElementById('id_centro_custo');
    const btnRegistrarVeiculo = document.getElementById('id_registrar_veiculo');
    
    if (!localSelect || !setorSelect || !btnRegistrarVeiculo) return;

    const labelRegistrarVeiculo = btnRegistrarVeiculo.nextElementSibling;
    const veiculoContainer = document.getElementById('veiculo-container');
    const veiculoSelecao = document.getElementById('id_veiculo_selecao');
    
    const localValue = localSelect.value;
    const setorText = setorSelect.options[setorSelect.selectedIndex]?.text.trim() || '';

    let obrigatorio = false;

    if (localValue === 'EXTERNO') {
        obrigatorio = true;
    } else if (localValue === 'INTERNO' && setorText.toUpperCase() === 'REVISAO DE VEICULO') {
        obrigatorio = true;
    }

    if (obrigatorio) {
        // Marca o checkbox e mostra o select
        btnRegistrarVeiculo.checked = true;
        btnRegistrarVeiculo.dispatchEvent(new Event('change'));
        
        // Impede que o usuário desmarque o checkbox
        btnRegistrarVeiculo.onclick = function(e) { e.preventDefault(); };
        btnRegistrarVeiculo.classList.add('opacity-50', 'cursor-not-allowed');
        if (labelRegistrarVeiculo) {
            labelRegistrarVeiculo.classList.remove('cursor-pointer');
            labelRegistrarVeiculo.classList.add('cursor-not-allowed');
        }

        // Adiciona asterisco vermelho
        if (labelRegistrarVeiculo && !labelRegistrarVeiculo.querySelector('.text-red-400')) {
            labelRegistrarVeiculo.innerHTML += ' <span class="text-red-400">*</span>';
        }
        
        // Torna a seleção obrigatória
        if (veiculoSelecao) {
            veiculoSelecao.setAttribute('required', 'required');
            // Remove a opção "Nenhum" se for obrigatório, ou deixa como value="" para barrar no validate nativo
        }
        
    } else {
        // Remove travas
        btnRegistrarVeiculo.onclick = null;
        btnRegistrarVeiculo.classList.remove('opacity-50', 'cursor-not-allowed');
        if (labelRegistrarVeiculo) {
            labelRegistrarVeiculo.classList.add('cursor-pointer');
            labelRegistrarVeiculo.classList.remove('cursor-not-allowed');
            
            const asterisk = labelRegistrarVeiculo.querySelector('.text-red-400');
            if (asterisk) asterisk.remove();
        }

        // Remove required da seleção
        if (veiculoSelecao) {
            veiculoSelecao.removeAttribute('required');
        }
    }
}

// Iniciar a regra nos listeners
document.addEventListener('DOMContentLoaded', function() {
    const localSelectElem = document.getElementById('select-local-trabalho');
    const setorSelectElem = document.getElementById('id_centro_custo');

    if (localSelectElem) {
        localSelectElem.addEventListener('change', aplicarRegraVeiculoObrigatorio);
    }

    if (setorSelectElem) {
        // Tratar evento do Select2, que não dispara o change nativo facilmente às vezes
        $('#id_centro_custo').on('change', aplicarRegraVeiculoObrigatorio);
        setorSelectElem.addEventListener('change', aplicarRegraVeiculoObrigatorio);
    }

    // ========================================================================
    // Verificação AJAX de Plantão
    // ========================================================================
    const inputData = document.querySelector('input[name="data_apontamento"]') || document.querySelector('#id_data_apontamento');
    const inputHora = document.querySelector('input[name="hora_inicio"]') || document.querySelector('#id_hora_inicio');
    const checkboxPlantao = document.querySelector('input[name="em_plantao"]') || document.querySelector('#id_em_plantao');
    const aviso = document.querySelector('#plantao-lock-msg');

    if (!inputData || !inputHora) {
        console.error("ERRO: Campos de Data ou Hora não encontrados pelo JavaScript! Verifique os seletores.");
    }

    async function verificarPlantaoAjax() {
        console.log('Disparando AJAX...');
        if (!checkboxPlantao) return;

        // Bloqueia o checkbox enquanto vai consultar a API
        checkboxPlantao.disabled = true;

        const dataVal = inputData ? inputData.value : '';
        const horaVal = inputHora ? inputHora.value : '';
        
        const colabSelect = document.querySelector('#id_colaborador');
        const colabVal = colabSelect ? colabSelect.value : '';
        
        console.log(`Verificando plantão para Data: ${dataVal || 'now()'}, Hora: ${horaVal || 'now()'}, Colab: ${colabVal}`);

        try {
            const response = await fetch(`{{ route('apontamentos.api.plantao') }}?data=${dataVal}&hora=${horaVal}&colaborador=${colabVal}`);
            const data = await response.json();
            
            console.log('Resposta do Back-end:', data);
            
            if (data.pode_plantao) {
                // Habilita o checkbox. O Tailwind retira as classes "disabled:" (opacity e cursor) nativamente.
                checkboxPlantao.disabled = false;
                if (aviso) {
                    aviso.style.display = 'none';
                    aviso.classList.add('hidden');
                }
                console.log('Plantão liberado!');
            } else {
                // Desabilita o checkbox. O Tailwind reaplica as classes "disabled:" nativamente.
                checkboxPlantao.disabled = true;
                if (checkboxPlantao.checked) {
                    checkboxPlantao.checked = false;
                    checkboxPlantao.dispatchEvent(new Event('change'));
                }
                if (aviso) {
                    aviso.style.display = 'block';
                    aviso.classList.remove('hidden');
                }
                console.log('Plantão bloqueado!', data.motivo ? `- ${data.motivo}` : '');
            }
        } catch (error) {
            console.error("Erro ao verificar plantão:", error);
        }
    }

    // Array com os eventos que queremos escutar
    const eventos = ['change', 'input', 'blur'];

    // Aplica os eventos no campo de Data
    if (inputData) {
        eventos.forEach(evento => {
            inputData.addEventListener(evento, verificarPlantaoAjax);
        });
    }

    // Aplica os eventos no campo de Hora
    if (inputHora) {
        eventos.forEach(evento => {
            inputHora.addEventListener(evento, verificarPlantaoAjax);
        });
    }

    // Aplica o evento change no Colaborador, se existir
    const selectColaborador = document.querySelector('#id_colaborador');
    if (selectColaborador) {
        // Se usar select2, precisa atrelar via jQuery também
        if (typeof $ !== 'undefined') {
            $('#id_colaborador').on('change', verificarPlantaoAjax);
        }
        selectColaborador.addEventListener('change', verificarPlantaoAjax);
    }

    // Intervalo de 5 min
    setInterval(() => {
        verificarPlantaoAjax();
    }, 5 * 60 * 1000);

    // Chamada inicial
    verificarPlantaoAjax();

    aplicarRegraVeiculoObrigatorio();
});
</script>
<script src="{{ asset('js/offline-sync.js') }}"></script>
@endpush
