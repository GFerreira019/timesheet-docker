@extends('layouts.app')

@section('title', 'Gestão de Colaboradores')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 0;
    bottom: -20px;
    width: 2px;
    background: var(--border-color, #334155);
}
.timeline-item:last-child::before {
    bottom: 0;
}
.timeline-dot {
    position: absolute;
    left: -9px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--accent-primary, #3b82f6);
}
</style>

<!-- FontAwesome 6 — conforme Seção 6.1 do Design System -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    title="Gestão de Colaboradores" 
    subtitle="Gerencie a equipe e visualize o histórico"
    icon="fas fa-users"
    iconBg="from-orange-500 to-orange-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto px-4 pb-4 pt-1 sm:px-6 sm:pb-6 sm:pt-1">

    <div class="flex items-center gap-4 sm:gap-4 relative justify-between -mb-6">
        
        <form method="GET" action="{{ route('colaboradores.index') }}" class="flex flex-1 relative items-center">
            @if(request('cargo')) <input type="hidden" name="cargo" value="{{ request('cargo') }}"> @endif
            @if(request('setor')) <input type="hidden" name="setor" value="{{ request('setor') }}"> @endif
            @if(request('cidade_trabalho')) <input type="hidden" name="cidade_trabalho" value="{{ request('cidade_trabalho') }}"> @endif
            @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif

            <div class="relative group/search w-full sm:w-64 lg:w-96" id="container-live-search">
                <i class="fas fa-user absolute left-3 top-2.5 text-slate-500"></i>
                <input type="text" id="input-busca-nome" name="nome" value="{{ request('nome') }}" autocomplete="off" placeholder="Buscar colaborador..." 
                    class="w-full bg-slate-900 border border-slate-700 text-slate-200 placeholder-slate-500 text-sm rounded-lg pl-10 pr-10 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-colors shadow-sm">
            
                @if(request('nome'))
                    <a href="{{ route('colaboradores.index', request()->except('nome')) }}" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-red-400 transition-colors z-10" title="Limpar busca">
                        <i class="fas fa-times"></i>
                    </a>
                @else
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-indigo-400 transition-colors z-10" title="Pesquisar">
                        <i class="fas fa-search"></i>
                    </button>
                @endif
                <div id="dropdown-busca-nome" class="absolute z-[100] w-full mt-1 bg-slate-800 border border-slate-600 rounded-lg shadow-2xl hidden max-h-60 overflow-y-auto"></div>        
            </div>
        </form>

        <button type="button" onclick="abrirModalFiltros()" class="hidden sm:flex px-3 sm:px-4 py-2 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-slate-300 font-bold rounded-lg transition-colors items-center gap-2 text-sm flex-shrink-0">
            <i class="fas fa-filter"></i> <span class="hidden sm:inline">Filtrar</span>
        </button>

        <button type="button" onclick="abrirModalNovo()" class="flex-shrink-0 px-3 sm:px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all flex items-center gap-2 text-sm">
            <i class="fas fa-plus"></i> <span class="hidden sm:inline">Novo Colaborador</span>
        </button>
        
    </div>
</div>


<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6">
    {{-- ============================================================
         TABELA DE COLABORADORES
         ============================================================ --}}
    <div class="overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg">
        <table class="min-w-full divide-y divide-slate-700/50">
            <thead>
                <tr class="bg-slate-900/30">
                    <th class="py-3 px-4 text-left text-xs font-bold theme-text-primary uppercase tracking-wider">Nome</th>
                    <th class="py-3 px-4 text-left text-xs font-bold theme-text-primary uppercase tracking-wider">Cargo</th>
                    <th class="py-3 px-4 text-left text-xs font-bold theme-text-primary uppercase tracking-wider">Setor</th>
                    <th class="py-3 px-4 text-left text-xs font-bold theme-text-primary uppercase tracking-wider">Nível Acesso</th>
                    <th class="py-3 px-4 text-left text-xs font-bold theme-text-primary uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-center text-xs font-bold theme-text-primary uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($colaboradores as $colab)
                <tr class="hover:bg-slate-800/50 transition group">
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-normal break-words min-w-[200px] max-w-xs align-middle" id="cell-nome_completo-{{ $colab->id }}">{{ $colab->nome_completo }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-normal break-words min-w-[150px] max-w-[250px] align-middle" id="cell-cargo-{{ $colab->id }}">{{ $colab->cargo ?? '-' }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap align-middle" id="cell-setor-{{ $colab->id }}">{{ $colab->setorRelacionamento->nome ?? '-' }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap align-middle">
                        <span class="px-2 py-1 bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 rounded text-[10px] font-bold">{{ $colab->nivel_acesso }}</span>
                    </td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap align-middle" id="cell-status-{{ $colab->id }}">
                        @if($colab->data_demissao)
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 border border-red-500/30 rounded text-xs">Inativo</span>
                        @else
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 border border-green-500/30 rounded text-xs">Ativo</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center whitespace-nowrap align-middle">
                        <div class="flex items-center justify-center gap-2">
                            
                            <button type="button" 
                                    onclick="abrirModalFicha(this)" 
                                    data-info="{{ json_encode($colab) }}" 
                                    class="p-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
                                    title="Ficha do Colaborador">
                                <i class="fas fa-user-edit text-blue-400 text-lg"></i>
                            </button>

                            <button type="button" 
                                    class="btn-historico p-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
                                    data-id="{{ $colab->id }}" 
                                    data-nome="{{ $colab->nome_completo }}"
                                    title="Ver Histórico">
                                <i class="fas fa-history text-purple-400 text-lg"></i>
                            </button>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-3 px-4 text-center text-sm text-slate-400">
                        Nenhum colaborador encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginação -->
        @if($colaboradores->hasPages())
        <div class="p-4 border-t border-slate-700/50 bg-slate-800/50">
            {{ $colaboradores->links() }}
        </div>
        @endif
    </div>
</div>



{{-- ==========================================
     MODAL DE HISTÓRICO (TIMELINE)
     ========================================== --}}
<div id="modal-historico" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4 bg-black/60">
    <!-- Container -->
    <div class="w-full max-w-2xl bg-slate-800 border border-slate-700/50 shadow-2xl rounded-xl overflow-y-auto transform transition-all scale-95 opacity-0 duration-300 flex flex-col" id="modal-historico-content" style="max-height: 90vh;">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50 flex-shrink-0">
            <h3 class="text-lg text-slate-200 font-bold flex items-center gap-2">
                <i class="fas fa-history text-purple-400"></i> Histórico de Alterações
            </h3>
            <button onclick="fecharModalHistorico()" class="p-2 rounded-lg hover:bg-slate-700 transition text-slate-400">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Conteúdo -->
        <div class="p-6 flex flex-col flex-1">
            <p id="modal-historico-nome" class="text-sm text-slate-200 font-bold mb-4 px-3 py-2 rounded-lg border border-slate-700 bg-slate-900 inline-block"></p>

            <!-- Timeline Container (Scrollable) -->
            <div id="timeline-container" class="relative pl-4 overflow-y-auto pr-2 custom-scrollbar flex-1 pb-4 min-h-[150px]">
                <!-- Inject via JS -->
            </div>
        </div>
        
    </div>
</div>

{{-- ==========================================
     MODAL DE FILTROS
     ========================================== --}}
<div id="modal-filtros" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-filter text-indigo-400"></i>
                        Filtrar Colaboradores
                    </h3>
                    <button type="button" onclick="fecharModalFiltros()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form method="GET" action="{{ route('colaboradores.index') }}" class="p-6" id="form-filtros-dinamicos">
                    @if(request('nome')) 
                        <input type="hidden" name="nome" value="{{ request('nome') }}"> 
                    @endif
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Tipo de Filtro</label>
                            <div class="relative">
                                <select id="select-tipo-filtro" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="" disabled selected>Selecione o que deseja filtrar...</option>
                                    <option value="cargo" @if(request('cargo')) selected @endif>Cargo</option>
                                    <option value="setor_id" @if(request('setor_id')) selected @endif>Setor</option>
                                    <option value="nivel_acesso" @if(request('nivel_acesso')) selected @endif>Nível de Acesso</option>
                                    <option value="status" @if(request('status')) selected @endif>Status</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <div id="container-valor-filtro" class="hidden">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1" id="label-valor-filtro">Selecione a opção</label>
                            
                            <div class="relative">
                                <select id="filtro-cargo" name="cargo" disabled class="filtro-input hidden w-full bg-slate-800 border border-slate-600 text-white text-xs rounded-lg p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="">Selecione um Cargo...</option>
                                    @foreach($cargos as $cargo)
                                    <option value="{{ $cargo }}" @if(request('cargo') == $cargo) selected @endif>{{ $cargo }}</option>
                                    @endforeach
                                </select>
                                
                                <select id="filtro-setor_id" name="setor_id" disabled class="filtro-input hidden w-full bg-slate-800 border border-slate-600 text-white text-xs rounded-lg p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="">Selecione um Setor...</option>
                                    @foreach($setores as $setor)
                                    <option value="{{ $setor->id }}" @if(request('setor_id') == $setor->id) selected @endif>{{ $setor->nome }}</option>
                                    @endforeach
                                </select>

                                <select id="filtro-nivel_acesso" name="nivel_acesso" disabled class="filtro-input hidden w-full bg-slate-800 border border-slate-600 text-white text-xs rounded-lg p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="">Selecione um Nível de Acesso...</option>
                                    @foreach($niveis_acesso as $nivel_acesso)
                                    <option value="{{ $nivel_acesso }}" @if(request('nivel_acesso') == $nivel_acesso) selected @endif>{{ $nivel_acesso }}</option>
                                    @endforeach
                                </select>

                                <select id="filtro-status" name="status" disabled class="filtro-input hidden w-full bg-slate-800 border border-slate-600 text-white text-xs rounded-lg p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                    <option value="ativo" @if(request('status') == 'ativo') selected @endif>Ativo</option>
                                    <option value="inativo" @if(request('status') == 'inativo') selected @endif>Inativo</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-6 flex justify-between gap-3 border-t border-slate-800 mt-6">
                        <a href="{{ route('colaboradores.index') }}" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600 flex items-center gap-2"> 
                            <i class="fas fa-eraser"></i> Limpar Filtros 
                        </a>
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

{{-- ==========================================
     MODAL NOVO COLABORADOR
     ========================================== --}}
<div id="modal-novo-colaborador" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-3xl fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user-plus text-indigo-400"></i>
                        Cadastrar Colaborador
                    </h3>
                    <button type="button" onclick="fecharModalNovo()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form method="POST" action="{{ route('colaboradores.store') }}" class="p-6">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="col-span-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Nome Completo *</label>
                            <input type="text" name="nome_completo" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <div class="col-span-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Nível de Acesso (Timesheet) *</label>
                            <select name="nivel_acesso" id="novo_nivel_acesso" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                <option value="OPERACIONAL">OPERACIONAL <span class="text-xs theme-text-secondary">(Apontamentos próprios)</span></option>
                                <option value="GESTOR">COORDENADOR <span class="text-xs theme-text-secondary">(Apontamentos próprios e de suas obras)</span></option>
                                <option value="ADMIN">ADMINISTRADOR <span class="text-xs theme-text-secondary">(Acesso total)</span></option>
                                <option value="GERENCIAL">GERENTE <span class="text-xs theme-text-secondary">(Apontamentos do setor e aprovações)</span></option>
                                <option value="SAC">BACKOFFICE <span class="text-xs theme-text-secondary">(Apontamentos do setor)</span></option>
                            </select>
                            <div id="container-btn-vincular-novo" class="hidden mt-2">
                                <button type="button" onclick="abrirModalVincularSetores('novo')" class="w-full px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold rounded-lg transition-colors border border-slate-600 flex items-center justify-center gap-2 text-sm">
                                    <i class="fas fa-network-wired text-indigo-400"></i> Vincular Setores <span id="badge-setores-novo" class="bg-indigo-600 text-white rounded-full px-2 py-0.5 ml-1 hidden">0</span>
                                </button>
                            </div>
                            <div id="hidden-inputs-setores-novo"></div>
                        </div>
                        <div class="col-span-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Cargo *</label>
                            <div class="flex items-center gap-2">
                                <select name="cargo" id="select-cargo-novo" required class="flex-1 w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">Selecione</option>
                                    @foreach($cargos as $cargo)
                                    <option value="{{ $cargo }}">{{ $cargo }}</option>
                                    @endforeach
                                </select>
                                <button type="button" onclick="abrirModalNovaOpcao('select-cargo-novo', 'Cargo')" class="p-3 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg border border-slate-600 transition-colors" title="Cadastrar novo">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Matrícula *</label>
                            <input type="text" name="id_colaborador" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Telefone</label>
                            <input type="text" id="telefone" name="telefone" maxlength="15" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        
                        <div class="col-span-1 sm:col-span-2 grid grid-cols-4 gap-2 relative container-cidade-uf">
                            <div class="col-span-3 relative">
                                <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Cidade de Moradia</label>
                                <input type="text" name="cidade_moradia" autocomplete="off" placeholder="Digite para buscar..." 
                                       class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none input-autocomplete-cidade relative z-10">
                                <div class="div-dropdown-cidades absolute z-[99999] w-full mt-1 bg-slate-800 border border-slate-600 rounded-lg shadow-2xl hidden max-h-48 overflow-y-auto"></div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">UF</label>
                                <input type="text" name="uf_moradia" readonly tabindex="-1" 
                                       class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-slate-400 text-sm cursor-not-allowed text-center font-bold input-uf-estado relative z-10">
                            </div>
                        </div>
                        <div class="col-span-1 sm:col-span-2 grid grid-cols-4 gap-2 relative container-cidade-uf">
                            <div class="col-span-3 relative">
                                <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Cidade de Trabalho</label>
                                <input type="text" name="cidade_trabalho" autocomplete="off" placeholder="Digite para buscar..." 
                                       class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none input-autocomplete-cidade relative z-10">
                                <div class="div-dropdown-cidades absolute z-[99999] w-full mt-1 bg-slate-800 border border-slate-600 rounded-lg shadow-2xl hidden max-h-48 overflow-y-auto"></div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">UF</label>
                                <input type="text" name="uf_trabalho" readonly tabindex="-1" 
                                       class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-slate-400 text-sm cursor-not-allowed text-center font-bold input-uf-estado relative z-10">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Setor *</label>
                            <div class="flex items-center gap-2">
                                <select name="setor_id" id="select-setor-novo" required class="flex-1 w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">Selecione</option>
                                    @foreach($setores as $setor)
                                    <option value="{{ $setor->id }}">{{ $setor->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="col-span-1 sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Data de Admissão *</label>
                                <input type="date" name="data_admissao" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all [color-scheme:dark]">
                            </div>
                        </div>
                    </div>
                    <div class="pt-6 flex justify-end gap-3 border-t border-slate-800 mt-6">
                        <button type="button" onclick="fecharModalNovo()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all text-sm flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Salvar Colaborador
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Nova Opção (Substitui o prompt) --}}
<div id="modal-nova-opcao" class="relative z-[60] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-[60] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-sm fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-plus-circle text-indigo-400"></i>
                        Cadastrar Nova Opção
                    </h3>
                    <button type="button" onclick="fecharModalNovaOpcao()" class="text-gray-400 hover:text-white text-xl font-bold transition-colors">&times;</button>
                </div>
                <div class="p-5">
                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1" id="label-nova-opcao">Digite o novo valor:</label>
                    <input type="text" id="input-nova-opcao" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all" placeholder="Ex: Novo Valor...">
                    
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" onclick="fecharModalNovaOpcao()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="button" onclick="confirmarNovaOpcao()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg transition-all text-sm">
                            Adicionar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ==========================================
     MODAL VINCULAR SETORES (GERENCIAL / SAC)
     ========================================== --}}
<div id="modal-vincular-setores" class="relative hidden" style="z-index: 9999;" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm" style="z-index: 9998;"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto" style="z-index: 9999;">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-network-wired text-indigo-400"></i>
                        Vincular Setores
                    </h3>
                    <button type="button" onclick="fecharModalVincularSetores()" class="text-gray-400 hover:text-white text-xl font-bold transition-colors">&times;</button>
                </div>
                <div class="p-5">
                    <p class="text-sm text-slate-400 mb-4">Selecione os setores que este colaborador poderá gerenciar/visualizar:</p>
                    
                    <div class="max-h-60 overflow-y-auto pr-2 custom-scrollbar space-y-2" id="lista-setores-checkboxes">
                        @foreach($setores as $setor)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-700 bg-slate-800 cursor-pointer hover:bg-slate-700 transition-colors">
                            <input type="checkbox" value="{{ $setor->id }}" class="checkbox-setor-vinculo w-5 h-5 text-indigo-600 bg-slate-900 border-slate-600 rounded focus:ring-indigo-500 focus:ring-2">
                            <span class="text-sm text-slate-200 font-medium">{{ $setor->nome }}</span>
                        </label>
                        @endforeach
                    </div>
                    
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" onclick="fecharModalVincularSetores()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="button" onclick="confirmarVincularSetores()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg transition-all text-sm">
                            Confirmar Seleção
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ==========================================
     MODAL FICHA DO COLABORADOR
     ========================================== --}}
<div id="modal-ficha" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-4xl bg-slate-800 border border-slate-700/50 shadow-2xl rounded-xl overflow-y-auto transform transition-all scale-95 opacity-0 duration-300 flex flex-col" id="modal-ficha-content" style="max-height: 90vh;">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50 flex-shrink-0 bg-slate-900 rounded-t-xl">
            <h3 class="text-lg text-slate-200 font-bold flex items-center gap-2">
                <i class="fas fa-address-card text-blue-400"></i>
                Ficha do Colaborador
            </h3>
            <button onclick="fecharModalFicha()" class="p-2 rounded-lg hover:bg-slate-700 transition text-slate-400">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="p-6 flex-1">
            <form id="form-ficha" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nome Completo -->
                    <div class="relative group">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Nome Completo *</label>
                        <input type="text" name="nome_completo" disabled required class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>

                    <!-- Matrícula / ID (Bloqueado definitivo) -->
                    <div>
                        <label class="block text-xs font-medium mb-1 text-slate-400">Matrícula *</label>
                        <input type="text" name="id_colaborador" disabled class="w-full bg-slate-900/50 border border-slate-700 text-slate-500 rounded-lg p-3 outline-none cursor-not-allowed">
                    </div>

                    <!-- Nivel Acesso -->
                    <div class="relative group">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nível de Acesso (Timesheet) *</label>
                        <select name="nivel_acesso" disabled required class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none">
                            <option value="OPERACIONAL">OPERACIONAL <span class="text-xs theme-text-secondary">(Apontamentos próprios)</span></option>
                            <option value="GESTOR">COORDENADOR <span class="text-xs theme-text-secondary">(Apontamentos próprios e de suas obras)</span></option>
                            <option value="ADMIN">ADMINISTRADOR <span class="text-xs theme-text-secondary">(Acesso total)</span></option>
                            <option value="GERENCIAL">GERENTE <span class="text-xs theme-text-secondary">(Apontamentos do setor e aprovações)</span></option>
                            <option value="SAC">BACKOFFICE <span class="text-xs theme-text-secondary">(Apontamentos do setor)</span></option>
                        </select>
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <div id="container-btn-vincular-ficha" class="hidden mt-2">
                            <button type="button" onclick="abrirModalVincularSetores('ficha')" class="w-full px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold rounded-lg transition-colors border border-slate-600 flex items-center justify-center gap-2 text-xs">
                                <i class="fas fa-network-wired text-indigo-400"></i> Vincular Setores <span id="badge-setores-ficha" class="bg-indigo-600 text-white rounded-full px-2 py-0.5 ml-1 hidden">0</span>
                            </button>
                        </div>
                        <div id="hidden-inputs-setores-ficha"></div>
                    </div>

                    <!-- Cargo -->
                    <div class="relative group">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Cargo *</label>
                        <select name="cargo" disabled required class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none">
                            <option value="">Selecione</option>
                            @foreach($cargos as $cargo)
                                <option value="{{ $cargo }}">{{ $cargo }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>

                    <!-- Setor -->
                    <div class="relative group">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Setor *</label>
                        <select name="setor_id" disabled required class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none">
                            <option value="">Selecione</option>
                            @foreach($setores as $setor)
                                <option value="{{ $setor->id }}">{{ $setor->nome }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>

                    <!-- Cidade Moradia -->
                    <div class="relative group container-cidade-uf">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Cidade de Moradia</label>
                        <input type="text" name="cidade_moradia" disabled autocomplete="off" class="input-autocomplete-cidade w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100 z-10" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <div class="div-dropdown-cidades absolute z-[99999] w-full mt-1 bg-slate-800 border border-slate-600 rounded-lg shadow-xl hidden max-h-48 overflow-y-auto"></div>
                        <input type="hidden" name="uf_moradia" class="input-uf-estado" disabled>
                    </div>

                    <!-- Cidade Trabalho -->
                    <div class="relative group container-cidade-uf">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Cidade de Trabalho</label>
                        <input type="text" name="cidade_trabalho" disabled autocomplete="off" class="input-autocomplete-cidade w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100 z-10" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <div class="div-dropdown-cidades absolute z-[99999] w-full mt-1 bg-slate-800 border border-slate-600 rounded-lg shadow-xl hidden max-h-48 overflow-y-auto"></div>
                        <input type="hidden" name="uf_trabalho" class="input-uf-estado" disabled>
                    </div>

                    <!-- Telefone -->
                    <div class="relative group">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Telefone</label>
                        <input type="text" name="telefone" disabled class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>

                    <!-- Data Admissão (Bloqueado definitivo) -->
                    <div>
                        <label class="block text-xs font-medium mb-1 text-slate-400">Data de Admissão *</label>
                        <input type="date" name="data_admissao" disabled class="w-full bg-slate-900/50 border border-slate-700 text-slate-500 rounded-lg p-3 outline-none cursor-not-allowed [color-scheme:dark]">
                    </div>

                    <!-- Data Demissão -->
                    <div class="relative group">
                        <label class="block text-xs font-medium mb-1 text-slate-400">Data de Demissão</label>
                        <input type="date" name="data_demissao" disabled class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all [color-scheme:dark]">
                        <button type="button" onclick="desbloquearCampo(this)" class="absolute right-3 top-[28px] text-slate-500 hover:text-indigo-400 transition-colors opacity-50 hover:opacity-100" title="Editar informação">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Rodapé Dinâmico -->
                <div id="rodape-edicao" class="hidden mt-8 pt-6 border-t border-slate-700/50 bg-slate-800/30 -mx-6 px-6 -mb-6 pb-6 rounded-b-xl">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div id="container-vigencia" class="w-full md:w-1/3 hidden">
                            <label class="block text-xs font-bold mb-1 text-indigo-400">Mês de Vigência das Alterações *</label>
                            <input type="month" name="data_vigencia" id="ficha-data-vigencia" class="w-full bg-slate-900 border border-indigo-500/50 text-white rounded-lg p-3 text-sm uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors shadow-[0_0_15px_rgba(99,102,241,0.2)] [color-scheme:dark]">
                            <p class="text-[10px] text-slate-400 mt-1"><i class="fas fa-info-circle"></i> A partir de quando estas alterações são válidas.</p>
                        </div>
                        
                        <div class="flex justify-end gap-3">
                            <button type="button" onclick="fecharModalFicha()" class="px-5 py-3 text-sm font-bold rounded-lg text-slate-400 hover:bg-slate-700 border border-slate-600 transition-colors">Cancelar</button>
                            <button type="submit" class="px-6 py-3 text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow-[0_4px_15px_rgba(79,70,229,0.4)] transition-all flex items-center gap-2 transform hover:scale-105">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Listas únicas extraídas do Controller e convertidas para JS
    const listasJSON = {
        cargo: @json($cargos),
        setor: @json($setores),
        cidade: @json($cidades),
    };

    // Dicionário amigável para traduzir as chaves do JSON de histórico
    const dicCampos = {
        'nome_completo': 'Nome Completo',
        'nivel_acesso': 'Nível de Acesso (Timesheet)',
        'cargo': 'Cargo',
        'setor': 'Setor',
        'cidade_moradia': 'Cidade de Moradia',
        'cidade_trabalho': 'Cidade de Trabalho',
        'data_admissao': 'Data de Admissão',
        'data_demissao': 'Data de Demissão'
    };

    let currentColabData = {};
    let currentSelectIdForNovaOpcao = null;

    function abrirModalNovaOpcao(selectId, label) {
        if (!selectId) {
            selectId = 'edit-valor';
            const campoSelect = document.getElementById('edit-campo');
            label = campoSelect.options[campoSelect.selectedIndex].text;
        }

        currentSelectIdForNovaOpcao = selectId;
        const modal = document.getElementById('modal-nova-opcao');
        const input = document.getElementById('input-nova-opcao');
        const title = document.getElementById('label-nova-opcao');
        
        if (title) title.innerText = `Digite o novo valor para ${label}:`;
        
        input.value = '';
        modal.classList.remove('hidden');
        
        setTimeout(() => input.focus(), 100);
    }

    function fecharModalNovaOpcao() {
        document.getElementById('modal-nova-opcao').classList.add('hidden');
        currentSelectIdForNovaOpcao = null;
    }

    function confirmarNovaOpcao() {
        const input = document.getElementById('input-nova-opcao');
        const novoValor = input.value.trim();
        
        if (novoValor && currentSelectIdForNovaOpcao) {
            const select = document.getElementById(currentSelectIdForNovaOpcao);
            
            if (select) {
                const novaOption = new Option(novoValor, novoValor, true, true);
                select.add(novaOption);

                if (currentSelectIdForNovaOpcao === 'edit-valor') {
                    const campoSelect = document.getElementById('edit-campo');
                    const chaveInterna = campoSelect.value.includes('cidade') ? 'cidade' : campoSelect.value;
                    if(!listasJSON[chaveInterna].includes(novoValor)){
                        listasJSON[chaveInterna].push(novoValor);
                    }
                }
            }
            fecharModalNovaOpcao();
        }
    }

    // ----------------------------------------------------------------------
    // LÓGICA DO MODAL DE EDIÇÃO
    // ----------------------------------------------------------------------
    
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-historico').forEach(btn => {
            btn.addEventListener('click', function() {
                abrirModalHistorico(this.dataset.id, this.dataset.nome);
            });
        });

        const inputNovaOpcao = document.getElementById('input-nova-opcao');
        if(inputNovaOpcao) {
            inputNovaOpcao.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') confirmarNovaOpcao();
            });
        }
        
        const selectNivelNovo = document.querySelector('select[name="nivel_acesso"]#novo_nivel_acesso');
        if (selectNivelNovo) {
            selectNivelNovo.addEventListener('change', function() {
                const isGerencialOrSac = ['GERENCIAL', 'SAC'].includes(this.value);
                document.getElementById('container-btn-vincular-novo').classList.toggle('hidden', !isGerencialOrSac);
            });
        }

        const selectNivelFicha = document.querySelector('#modal-ficha select[name="nivel_acesso"]');
        if (selectNivelFicha) {
            selectNivelFicha.addEventListener('change', function() {
                const isGerencialOrSac = ['GERENCIAL', 'SAC'].includes(this.value);
                document.getElementById('container-btn-vincular-ficha').classList.toggle('hidden', !isGerencialOrSac);
            });
        }
    });

    let contextVincularSetores = null;
    let setoresSelecionadosFicha = [];
    let setoresSelecionadosNovo = [];

    function abrirModalVincularSetores(context) {
        contextVincularSetores = context;
        const modal = document.getElementById('modal-vincular-setores');
        const checkboxes = document.querySelectorAll('.checkbox-setor-vinculo');
        
        const selecionados = context === 'novo' ? setoresSelecionadosNovo : setoresSelecionadosFicha;
        
        checkboxes.forEach(cb => {
            cb.checked = selecionados.includes(cb.value);
        });
        
        modal.classList.remove('hidden');
    }

    function fecharModalVincularSetores() {
        document.getElementById('modal-vincular-setores').classList.add('hidden');
        contextVincularSetores = null;
    }

    function confirmarVincularSetores() {
        const checkboxes = document.querySelectorAll('.checkbox-setor-vinculo:checked');
        const selecionados = Array.from(checkboxes).map(cb => cb.value);
        
        const containerInputs = document.getElementById(`hidden-inputs-setores-${contextVincularSetores}`);
        const badge = document.getElementById(`badge-setores-${contextVincularSetores}`);
        
        if (contextVincularSetores === 'novo') {
            setoresSelecionadosNovo = selecionados;
        } else {
            setoresSelecionadosFicha = selecionados;
        }
        
        // Atualizar inputs hidden
        containerInputs.innerHTML = '';
        selecionados.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'setores_vinculados[]';
            input.value = id;
            containerInputs.appendChild(input);
        });
        
        // Atualizar badge
        if (selecionados.length > 0) {
            badge.textContent = selecionados.length;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
        
        fecharModalVincularSetores();
    }

    // Máscara de Telefone (form novo)
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function (e) {
            let value = e.target.value;
            value = value.replace(/\D/g, '');
            value = value.substring(0, 11);
            value = value.replace(/^(\d{2})(\d)/g, "($1) $2");
            value = value.replace(/(\d)(\d{4})$/, "$1-$2");
            e.target.value = value;
        });
    }

    // Máscara de Telefone (Ficha)
    const telefoneFichaInput = document.querySelector('#modal-ficha input[name="telefone"]');
    if (telefoneFichaInput) {
        telefoneFichaInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, "");
            value = value.substring(0, 11);
            value = value.replace(/^(\d{2})(\d)/g, "($1) $2");
            value = value.replace(/(\d)(\d{4})$/, "$1-$2");
            e.target.value = value;
        }); 
    }

    function abrirModalFicha(btnElement) {
        try {
            const dados = JSON.parse(btnElement.getAttribute('data-info'));
            const form = document.getElementById('form-ficha');
            
            // Set action dinâmica para o formulário
            form.action = `/colaboradores/${dados.id}`;
            
            // Limpar/Resetar validações visuais prévias e campos preenchidos
            form.reset();

            const fields = ['id_colaborador', 'nome_completo', 'telefone', 'cargo', 'nivel_acesso', 'setor_id', 'cidade_moradia', 'cidade_trabalho'];
            fields.forEach(f => {
                if (form.elements[f]) {
                    form.elements[f].value = dados[f] || '';
                    form.elements[f].disabled = true;
                    form.elements[f].classList.add('bg-slate-900/50', 'text-slate-400', 'cursor-not-allowed');
                    form.elements[f].classList.remove('bg-slate-800', 'text-white', 'cursor-text');
                }
            });

            if (form.elements['uf']) {
                form.elements['uf'].value = dados['uf'] || '';
            }

            if (form.elements['data_admissao']) {
                form.elements['data_admissao'].value = dados.data_admissao ? String(dados.data_admissao).substring(0, 10) : '';
                form.elements['data_admissao'].disabled = true;
                form.elements['data_admissao'].classList.add('bg-slate-900/50', 'text-slate-500', 'cursor-not-allowed');
            }

            if (form.elements['data_demissao']) {
                form.elements['data_demissao'].value = dados.data_demissao ? String(dados.data_demissao).substring(0, 10) : '';
                form.elements['data_demissao'].disabled = true;
                form.elements['data_demissao'].classList.add('bg-slate-900/50', 'text-slate-400', 'cursor-not-allowed');
                form.elements['data_demissao'].classList.remove('bg-slate-800', 'text-white', 'cursor-text');
            }

            // Ocultar o rodapé e resetar vigência
            const rodape = document.getElementById('rodape-edicao');
            if (rodape) rodape.classList.add('hidden');
            
            const containerVigencia = document.getElementById('container-vigencia');
            if (containerVigencia) containerVigencia.classList.add('hidden');
            
            const vigencia = document.getElementById('ficha-data-vigencia');
            if (vigencia) {
                vigencia.type = 'month';
                vigencia.value = '';
                vigencia.required = false;
            }

            setoresSelecionadosFicha = (dados.setores_vinculados || []).map(s => String(s.id));
            const selectNivel = form.elements['nivel_acesso'];
            const isGerencialOrSac = ['GERENCIAL', 'SAC'].includes(selectNivel.value);
            document.getElementById('container-btn-vincular-ficha').classList.toggle('hidden', !isGerencialOrSac);
            
            const badgeFicha = document.getElementById('badge-setores-ficha');
            if (setoresSelecionadosFicha.length > 0) {
                badgeFicha.textContent = setoresSelecionadosFicha.length;
                badgeFicha.classList.remove('hidden');
            } else {
                badgeFicha.classList.add('hidden');
            }

            const containerInputsFicha = document.getElementById('hidden-inputs-setores-ficha');
            containerInputsFicha.innerHTML = '';
            setoresSelecionadosFicha.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'setores_vinculados[]';
                input.value = id;
                containerInputsFicha.appendChild(input);
            });

            document.getElementById('modal-ficha').classList.remove('hidden');
            setTimeout(() => {
                const content = document.getElementById('modal-ficha-content');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);

        } catch (e) {
            console.error('Erro ao processar Ficha', e);
            alert('Falha ao abrir os dados do colaborador.');
        }
    }

    function fecharModalFicha() {
        const content = document.getElementById('modal-ficha-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            document.getElementById('modal-ficha').classList.add('hidden');
        }, 300);
    }

    function desbloquearCampo(btn) {
        const wrapper = btn.closest('.relative');
        if (!wrapper) return;
        
        const field = wrapper.querySelector('input, select');
        if (field && field.disabled) {
            field.disabled = false;
            field.classList.remove('bg-slate-900/50', 'text-slate-400', 'cursor-not-allowed');
            field.classList.add('bg-slate-800', 'text-white');
            field.focus();
            
            // Exibir o rodapé com o botão salvar
            document.getElementById('rodape-edicao').classList.remove('hidden');

            // Verifica quais campos estão desbloqueados atualmente no formulário
            const camposDesbloqueados = Array.from(document.querySelectorAll('#form-ficha input:not([disabled]), #form-ficha select:not([disabled])'))
                                            .map(el => el.name);

            // Lista de campos que exigem a data de vigência
            const camposComVigencia = ['nome_completo', 'cargo', 'nivel_acesso', 'setor_id', 'cidade_moradia', 'cidade_trabalho', 'telefone', 'data_demissao'];

            // Se algum dos campos desbloqueados estiver na lista, mostra a vigência. Caso contrário, esconde.
            const precisaVigencia = camposDesbloqueados.some(nome => camposComVigencia.includes(nome));

            if (precisaVigencia) {
                document.getElementById('container-vigencia').classList.remove('hidden');
                document.getElementById('ficha-data-vigencia').required = true;
            } else {
                document.getElementById('container-vigencia').classList.add('hidden');
                document.getElementById('ficha-data-vigencia').required = false;
                // Limpa o valor caso o utilizador tenha preenchido e depois cancelado a edição do campo
                document.querySelector('#container-vigencia input').value = ''; 
            }
        }
    }

    // Interceptar form-ficha para formatar a data_vigencia e garantir compatibilidade
    const formFicha = document.getElementById('form-ficha');
    if (formFicha) {
        formFicha.addEventListener('submit', function(e) {
            const vigencia = document.getElementById('ficha-data-vigencia');
            if (vigencia && vigencia.value && vigencia.value.length === 7) {
                vigencia.type = 'text'; // Muda temporariamente para aceitar YYYY-MM-DD
                vigencia.value = vigencia.value + '-01';
            }
        });
    }

    // ----------------------------------------------------------------------
    // LÓGICA DO MODAL DE HISTÓRICO
    // ----------------------------------------------------------------------

    // Os listeners do btn-historico foram movidos para o DOMContentLoaded acima

    async function abrirModalHistorico(id, nome) {
        const modal = document.getElementById('modal-historico');
        const container = document.getElementById('timeline-container');
        
        modal.classList.remove('hidden');
        document.getElementById('modal-historico-nome').innerText = nome;
        container.innerHTML = `<div class="text-center py-8 text-slate-500"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><br>Buscando log...</div>`;

        // Animação de entrada
        setTimeout(() => {
            const content = document.getElementById('modal-historico-content');
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);

        try {
            const response = await fetch(`/colaboradores/${id}/historico`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();

            if (data.length === 0) {
                container.innerHTML = `<div class="text-center py-8 text-slate-400"><i class="fas fa-folder-open text-2xl mb-2"></i><br>Nenhum histórico de alterações encontrado.</div>`;
                return;
            }

            let html = '';
            data.forEach(hist => {
                const dataFormatada = new Date(hist.created_at).toLocaleString('pt-BR');
                const userNome = hist.usuario_alteracao ? hist.usuario_alteracao.name : 'Sistema';
                
                // Extrair vigência da base de dados se existir, ou procurar nos campos alterados
                let vigenciaBadge = '';
                const vigenciaBase = hist.data_vigencia || (hist.campos_alterados && hist.campos_alterados.data_vigencia);
                if (vigenciaBase) {
                    const [ano, mes, dia] = vigenciaBase.substring(0, 10).split('-');
                    vigenciaBadge = `<span class="ml-3 px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-500/20 text-white-400 border border-indigo-500/30">Vigência: ${dia}/${mes}/${ano}</span>`;
                }
                
                // Formatar os campos alterados
                let camposHtml = '';
                for (const [chave, novoValor] of Object.entries(hist.campos_alterados)) {
                    if (chave === 'data_vigencia') continue; // Pular exibição como card de metadado
                    
                    const nomeAmigavel = dicCampos[chave] || chave;
                    const valorAnterior = (hist.dados_anteriores && hist.dados_anteriores[chave]) ? hist.dados_anteriores[chave] : '(vazio)';
                    const valorFinal = novoValor ? novoValor : '(vazio)';
                    
                    camposHtml += `
                        <div class="mt-2 p-3 rounded-lg border border-slate-700 bg-slate-900 text-sm">
                            <span class="font-semibold text-slate-400">${nomeAmigavel}</span><br>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-red-400 line-through">${valorAnterior}</span>
                                <i class="fas fa-arrow-right text-xs text-slate-500"></i>
                                <span class="text-green-400 font-medium">${valorFinal}</span>
                            </div>
                        </div>
                    `;
                }

                html += `
                    <div class="timeline-item relative pb-6">
                        <div class="timeline-dot"></div>
                        <div class="ml-4">
                            <div class="flex items-center justify-between mb-1">
                                <div>
                                    <span class="text-xs font-bold text-indigo-400">${dataFormatada}</span>
                                    ${vigenciaBadge}
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded border border-slate-700 bg-slate-900 text-slate-400">
                                    <i class="fas fa-user-edit mr-1"></i> ${userNome}
                                </span>
                            </div>
                            ${camposHtml}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

        } catch (error) {
            console.error(error);
            container.innerHTML = `<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><br>Erro ao carregar o histórico.</div>`;
        }
    }

    function fecharModalHistorico() {
        const content = document.getElementById('modal-historico-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            document.getElementById('modal-historico').classList.add('hidden');
        }, 300);
    }

    function abrirModalFiltros() {
        document.getElementById('modal-filtros').classList.remove('hidden');
    }
    
    function fecharModalFiltros() {
        document.getElementById('modal-filtros').classList.add('hidden');
    }

    function abrirModalNovo() {
        document.getElementById('modal-novo-colaborador').classList.remove('hidden');
        setoresSelecionadosNovo = [];
        const containerInputsNovo = document.getElementById('hidden-inputs-setores-novo');
        if (containerInputsNovo) containerInputsNovo.innerHTML = '';
        const badgeNovo = document.getElementById('badge-setores-novo');
        if (badgeNovo) badgeNovo.classList.add('hidden');
        const containerBtnNovo = document.getElementById('container-btn-vincular-novo');
        if (containerBtnNovo) containerBtnNovo.classList.add('hidden');
        const selectNivelNovo = document.querySelector('select[name="nivel_acesso"]#novo_nivel_acesso');
        if (selectNivelNovo) selectNivelNovo.value = 'OPERACIONAL';
    }
    
    function fecharModalNovo() {
        document.getElementById('modal-novo-colaborador').classList.add('hidden');
    }



    // Autocomplete de Cidades (Event Delegation + Debug)
    document.addEventListener('DOMContentLoaded', function() {
        // Anexa o listener de input no body para lidar com elementos dinâmicos
        document.body.addEventListener('input', function(e) {
            if (e.target && e.target.matches('.input-autocomplete-cidade')) {
                const inputCidade = e.target;
                
                const container = inputCidade.closest('.container-cidade-uf');
                if(!container) return;
                
                const dropdown = container.querySelector('.div-dropdown-cidades');
                const inputUf = container.querySelector('.input-uf-estado');

                if(!dropdown) return;

                clearTimeout(this.debounceTimer);
                const query = inputCidade.value.trim();
                
                if (query.length < 2) {
                    dropdown.innerHTML = '';
                    dropdown.classList.add('hidden');
                    return;
                }

                this.debounceTimer = setTimeout(() => {
                    fetch(`/colaboradores/api/cidades?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            dropdown.innerHTML = '';
                            if (data.length === 0) {
                                dropdown.innerHTML = '<div class="p-3 text-slate-400 text-sm">Nenhuma cidade encontrada.</div>';
                            } else {
                                data.forEach(cidade => {
                                    const div = document.createElement('div');
                                    div.className = 'p-3 hover:bg-slate-700 cursor-pointer text-sm text-slate-200 border-b border-slate-700/50 last:border-0';
                                    div.innerHTML = `<strong>${cidade.nome}</strong> - ${cidade.uf}`;
                                    div.addEventListener('click', () => {
                                        inputCidade.value = cidade.nome;
                                        if(inputUf) inputUf.value = cidade.uf;
                                        dropdown.classList.add('hidden');
                                    });
                                    dropdown.appendChild(div);
                                });
                            }
                            dropdown.classList.remove('hidden');
                        });
                }, 400);
            }
        });

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.div-dropdown-cidades');
            dropdowns.forEach(dropdown => {
                const container = dropdown.closest('.container-cidade-uf');
                if (container && !container.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });
    });

    // ==========================================
    // Live Search
    // ==========================================
    const inputBusca = document.getElementById('input-busca-nome');
    const dropdownBusca = document.getElementById('dropdown-busca-nome');
    let debounceBusca;

    if (inputBusca && dropdownBusca) {
        inputBusca.addEventListener('input', function() {
            clearTimeout(debounceBusca);
            const query = this.value.trim();
            if (query.length < 2) {
                dropdownBusca.classList.add('hidden');
                return;
            }
            debounceBusca = setTimeout(() => {
                fetch(`/colaboradores/api/buscar-nomes?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        dropdownBusca.innerHTML = '';
                        if (data.length === 0) {
                            dropdownBusca.innerHTML = '<div class="p-3 text-slate-400 text-sm">Nenhum colaborador encontrado.</div>';
                        } else {
                            data.forEach(colab => {
                                const div = document.createElement('div');
                                div.className = 'p-3 hover:bg-slate-700 cursor-pointer border-b border-slate-700/50 last:border-0';
                                div.innerHTML = `<div class="text-sm font-bold text-slate-200">${colab.nome_completo}</div><div class="text-xs text-slate-400">${colab.cargo || 'Sem Cargo'}</div>`;
                                div.addEventListener('click', () => {
                                    inputBusca.value = colab.nome_completo;
                                    dropdownBusca.classList.add('hidden');
                                    inputBusca.closest('form').submit();
                                });
                                dropdownBusca.appendChild(div);
                            });
                        }
                        dropdownBusca.classList.remove('hidden');
                    })
                    .catch(e => console.error('Erro na busca:', e));
            }, 300);
        });
        
        document.addEventListener('click', (e) => {
            if (!inputBusca.contains(e.target) && !dropdownBusca.contains(e.target)) {
                dropdownBusca.classList.add('hidden');
            }
        });
    }

    // ==========================================
    // Filtros Dinâmicos
    // ==========================================
    const selectTipo = document.getElementById('select-tipo-filtro');
    const containerValor = document.getElementById('container-valor-filtro');
    const labelValor = document.getElementById('label-valor-filtro');
    const inputsFiltro = document.querySelectorAll('.filtro-input');

    if (selectTipo) {
        selectTipo.addEventListener('change', function() {
            const tipoSelecionado = this.value;
            
            // Esconde e desabilita todos (name disabled para não sujar a URL)
            inputsFiltro.forEach(input => {
                input.classList.add('hidden');
                input.disabled = true;
                input.required = false;
            });

            if (tipoSelecionado) {
                // Mostra e habilita apenas o alvo
                const inputAlvo = document.getElementById(`filtro-${tipoSelecionado}`);
                if (inputAlvo) {
                    inputAlvo.classList.remove('hidden');
                    inputAlvo.disabled = false;
                    
                    labelValor.innerText = `Selecione o ${this.options[this.selectedIndex].text}`;
                    containerValor.classList.remove('hidden');
                }
            } else {
                containerValor.classList.add('hidden');
            }
        });
        
        // Trigger inicial caso exista uma seleção prévia persistida (ex: ao voltar à página com filtros ativos na URL)
        if(selectTipo.value) {
            selectTipo.dispatchEvent(new Event('change'));
        }
    }
</script>
@endpush
