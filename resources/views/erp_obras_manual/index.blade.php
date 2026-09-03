@extends('layouts.app')

@section('title', 'Gestão Manual de Obras (ERP)')

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

.tab-btn.active {
    border-bottom: 2px solid #6366f1; /* indigo-500 */
    color: #6366f1;
}
.tab-btn {
    border-bottom: 2px solid transparent;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    title="Acompanhamento de Projetos" 
    subtitle="Gerenciamento de Obras"
    icon="fas fa-hard-hat text-green-500"
    iconBg="from-green-500 to-green-700"
    backUrl="{{ route('painel') }}">
</x-page-header>
    
<div class="max-w-full xl:max-w-7xl mx-auto px-4 pb-4 pt-1 sm:px-6 sm:pb-6 sm:pt-1">

    @php
        $viewMode = request('view');
    @endphp

    <div class="flex items-center gap-2 sm:gap-4 relative justify-between mb-6">
                
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:flex-1">
            <form method="GET" action="{{ route('erp-obras-manual.index') }}" class="relative w-full sm:w-80 lg:w-96" id="searchContainer">
                {{-- Preserva o filtro da lente atual na busca --}}
                @if(request()->has('view'))
                    <input type="hidden" name="view" value="{{ request('view') }}">
                @endif
                
                <input type="text" name="busca" value="{{ request('busca') }}" id="searchInput" autocomplete="off" placeholder="Buscar por código ou nome..." class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
            </form>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
            <!-- Grupo de Botões de Lentes de Visão -->
            <div class="flex items-center bg-slate-900 border border-slate-700 rounded-lg p-1">
                <!-- Padrão -->
                <a href="{{ request()->fullUrlWithQuery(['view' => null]) }}" 
                   class="px-3 py-1.5 rounded-md text-sm transition-colors {{ blank($viewMode) ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-900/50' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' }}"
                   title="Visão Geral">
                    <i class="fas fa-filter"></i>
                </a>
                
                <!-- Gestores -->
                <a href="{{ request()->fullUrlWithQuery(['view' => 'gestores']) }}" 
                   class="px-3 py-1.5 rounded-md text-sm transition-colors {{ $viewMode === 'gestores' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-900/50' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' }}"
                   title="Visão: Gestores">
                    <i class="fas fa-user-tie"></i>
                </a>
                
                <!-- Financeiro -->
                <a href="{{ request()->fullUrlWithQuery(['view' => 'financeiro']) }}" 
                   class="px-3 py-1.5 rounded-md text-sm transition-colors {{ $viewMode === 'financeiro' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-900/50' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' }}"
                   title="Visão: Financeiro">
                    <i class="fas fa-dollar-sign"></i>
                </a>
                
                <!-- Cronograma -->
                <a href="{{ request()->fullUrlWithQuery(['view' => 'cronograma']) }}" 
                   class="px-3 py-1.5 rounded-md text-sm transition-colors {{ $viewMode === 'cronograma' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-900/50' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' }}"
                   title="Visão: Cronograma">
                    <i class="fas fa-calendar-alt"></i>
                </a>
            </div>

            <!-- Botão Nova Obra -->
            <button type="button" onclick="abrirModal(null)" class="px-3 sm:px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all flex items-center justify-center gap-2 text-sm flex-shrink-0">
                <i class="fas fa-plus"></i> <span class="hidden sm:inline">Nova Obra</span>
            </button>
        </div>
        
    </div>

    {{-- ============================================================
         TABELA DE OBRAS
         ============================================================ --}}
    <div class="overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg">
        <table class="min-w-full divide-y divide-slate-700/50">
            <thead>
                <tr class="bg-slate-900/30">
                    {{-- Colunas Iniciais Fixas --}}
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Nome do Projeto</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Unidade</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Setor</th>
                    
                    {{-- Colunas Dinâmicas via request('view') --}}
                    @if($viewMode === 'gestores')
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Comercial</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Implantação</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Manutenção</th>
                    
                    @elseif($viewMode === 'financeiro')
                        <th class="py-3 px-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Venda</th>
                        <th class="py-3 px-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Monitoramento</th>
                        <th class="py-3 px-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Licença</th>
                        <th class="py-3 px-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Manutenção</th>
                        <th class="py-3 px-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Locação</th>
                    
                    @elseif($viewMode === 'cronograma')
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider min-w-[150px]">Status e Avanço</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Início</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Fim</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Contrato</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Termo Entrega</th>
                    
                    @else
                        {{-- Visão Padrão --}}
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">CNPJ e Razão Social</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Localização</th>
                        <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Target</th>
                    @endif

                    {{-- Colunas Finais Fixas --}}
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Comentários</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider sticky right-0 bg-slate-900 z-20 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.3)]">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($obras as $obra)
                <tr class="hover:bg-slate-800/50 transition group">
                    
                    {{-- 1. Código, Status e Nome --}}
                    <td class="py-3 px-4 text-sm text-slate-300">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-slate-200">{{ $obra->projeto_codigo ?? '-' }}</span>
                            @if($obra->status_ativo)
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]" title="Ativo"></div>
                            @else
                                <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]" title="Inativo"></div>
                            @endif
                        </div>
                        <div class="font-semibold text-xs text-slate-400">{{ $obra->projeto_nome ?? '-' }}</div>
                    </td>
                    
                    {{-- 2. Unidade --}}
                    <td class="py-3 px-4 text-sm font-semibold uppercase text-slate-300">
                        {{ $obra->projeto_unidade ?? '-' }}
                    </td>
                    
                    {{-- 3. Setor / Etapa --}}
                    <td class="py-3 px-4 text-sm font-semibold uppercase text-slate-300">
                        {{-- Tenta buscar o nome da relação setor, com fallback para a coluna projeto_setor, e por fim '-' --}}
                        <div class="font-semibold text-slate-300">{{ $obra->setor->nome ?? $obra->projeto_setor ?? '-' }}</div>
                        @if($obra->projeto_etapa)
                            <div class="font-semibold text-xs text-slate-400"><span class="font-semibold text-slate-400 uppercase">em </span> {{ $obra->projeto_etapa }}</div>
                        @endif
                    </td>

                    {{-- Sessões Dinâmicas do Body --}}
                    @if($viewMode === 'gestores')
                        <td class="py-3 px-4 text-sm text-slate-300">
                            <div class="text-slate-300 font-semibold" title="Responsável Comercial: {{ $obra->liderComercial->nome_completo ?? '-' }}">{{ $obra->liderComercial->nome_completo ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4 text-sm text-slate-300">
                            <div class="text-slate-300 font-semibold truncate max-w-[180px]" title="Gerente: {{ $obra->gerenteImplantacao->nome_completo ?? '-' }}">{{ $obra->gerenteImplantacao->nome_completo ?? '-' }}</div>
                            <div class="text-xs text-slate-400 font-semibold truncate max-w-[180px]" title="Coordenadores: {{ $obra->coordenadoresProjeto->pluck('nome_completo')->join(', ') ?: '-' }}">{{ $obra->coordenadoresProjeto->pluck('nome_completo')->join(', ') ?: '-' }}</div>
                        </td>
                        <td class="py-3 px-4 text-sm text-slate-300">
                            <div class="text-slate-300 font-semibold truncate max-w-[180px]" title="Gerente: {{ $obra->gerenteManutencao->nome_completo ?? '-' }}">{{ $obra->gerenteManutencao->nome_completo ?? '-' }}</div>
                            <div class="text-xs text-slate-400 font-semibold truncate max-w-[180px]" title="Coordenadores: {{ $obra->coordenadoresProjeto->pluck('nome_completo')->join(', ') ?: '-' }}">{{ $obra->coordenadoresProjeto->pluck('nome_completo')->join(', ') ?: '-' }}</div>
                        </td>

                    @elseif($viewMode === 'financeiro')
                        <td class="py-3 px-4 text-sm text-right text-slate-300 font-semibold whitespace-nowrap">R$ {{ $obra->valor_venda ? number_format($obra->valor_venda, 2, ',', '.') : '0,00' }}</td>
                        <td class="py-3 px-4 text-sm text-right text-slate-300 font-semibold whitespace-nowrap">R$ {{ $obra->valor_monitoramento ? number_format($obra->valor_monitoramento, 2, ',', '.') : '0,00' }}</td>
                        <td class="py-3 px-4 text-sm text-right text-slate-300 font-semibold whitespace-nowrap">R$ {{ $obra->valor_licenca ? number_format($obra->valor_licenca, 2, ',', '.') : '0,00' }}</td>
                        <td class="py-3 px-4 text-sm text-right text-slate-300 font-semibold whitespace-nowrap">R$ {{ $obra->valor_manutencao ? number_format($obra->valor_manutencao, 2, ',', '.') : '0,00' }}</td>
                        <td class="py-3 px-4 text-sm text-right text-slate-300 font-semibold whitespace-nowrap">R$ {{ $obra->valor_locacao ? number_format($obra->valor_locacao, 2, ',', '.') : '0,00' }}</td>

                    @elseif($viewMode === 'cronograma')
                        <td class="py-3 px-4 text-sm text-slate-300 w-48">
                            <div class="text-xs font-semibold mb-1 text-slate-200">{{ $obra->projeto_status ?? '-' }}</div>
                            <div class="w-full bg-slate-700 font-semibold rounded-full h-1.5 mb-1" title="{{ $obra->projeto_avanco ?? 0 }}%">
                                <div class="bg-indigo-500 h-1.5 rounded-full transition-all" style="width: {{ $obra->projeto_avanco ?? 0 }}%"></div>
                            </div>
                            <div class="text-[10px] text-right font-semibold text-slate-400">{{ $obra->projeto_avanco ?? 0 }}%</div>
                        </td>
                        <td class="py-3 px-4 text-sm text-slate-300 font-semibold">{{ $obra->cronograma_inicio ? \Carbon\Carbon::parse($obra->cronograma_inicio)->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 text-sm text-slate-300 font-semibold">{{ $obra->cronograma_fim ? \Carbon\Carbon::parse($obra->cronograma_fim)->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 text-sm text-slate-300 font-semibold">{{ $obra->contrato_assinatura ? \Carbon\Carbon::parse($obra->contrato_assinatura)->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 text-sm text-slate-300 font-semibold">{{ $obra->termo_entrega ? \Carbon\Carbon::parse($obra->termo_entrega)->format('d/m/Y') : '-' }}</td>

                    @else
                        {{-- Visão Padrão --}}
                        <td class="py-3 px-4 text-sm text-slate-300">
                            <div class="font-bold text-slate-300">{{ $obra->cnpj ?? '-' }}</div>
                            <div class="text-xs font-semibold text-slate-500 truncate max-w-[200px]" title="{{ $obra->razao_social }}">{{ $obra->razao_social ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4 text-sm text-slate-300">
                            <div class="flex items-center gap-2 text-slate-300 font-semibold uppercase">
                                {{ $obra->cidade ?? '-' }}
                                @if($obra->pedagio)
                                    <i class="fas fa-road-barrier text-[11px] text-cyan-500" title="Possui Pedágio"></i>
                                @endif
                            </div>
                            <div class="uppercase text-xs font-semibold text-slate-500 truncate max-w-[220px]" title="{{ $obra->endereco }}">{{ $obra->endereco ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4 text-sm font-semibold text-slate-300">
                            {{ $obra->target ? \Carbon\Carbon::parse($obra->target)->format('m/Y') : '-' }}
                        </td>
                    @endif

                    {{-- Penúltima Fixo: Comentários --}}
                    <td class="py-3 px-4 text-center text-sm text-slate-300">
                        @if($obra->comentarios)
                            <i class="fas fa-comment text-indigo-400 text-lg cursor-help hover:text-indigo-300 transition-colors" title="{{ $obra->comentarios }}"></i>
                        @else
                            <i class="fas fa-comment text-slate-600 text-lg opacity-30"></i>
                        @endif
                    </td>

                    {{-- Última Fixo: Ações --}}
                    <td class="py-3 px-4 text-center sticky right-0 bg-slate-800 group-hover:bg-slate-700/80 z-10 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.3)] transition-colors">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Botão Edição -->
                            <button type="button" 
                                    onclick="abrirModal({{ $obra->toJson() }})" 
                                    class="p-2 rounded-md text-slate-400 hover:bg-slate-600 hover:text-white transition-colors"
                                    title="Editar Obra">
                                <i class="fas fa-edit text-blue-400 text-lg"></i>
                            </button>
                            
                            <!-- Botão Visualização -->
                            <a href="{{ route('erp-obras-manual.show', $obra->id) }}" 
                               class="p-2 rounded-md text-slate-400 hover:bg-slate-600 hover:text-white transition-colors"
                               title="Visualizar Detalhes">
                                <i class="fas fa-eye text-emerald-400 text-lg"></i>
                            </a>
                        </div>
                    </td>
                    
                </tr>
                @empty
                <tr>
                    <td colspan="100%" class="py-8 px-4 text-center text-sm text-slate-400">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <i class="fas fa-folder-open text-4xl text-slate-600"></i>
                            <p>Nenhuma obra encontrada para esta visualização.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginação preservando as Query Strings (busca e view atual) -->
        @if($obras->hasPages())
        <div class="p-4 border-t border-slate-700/50 bg-slate-800/50">
            {{ $obras->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ==========================================
     MODAL FICHA DA OBRA
     ========================================== --}}
<div id="modal-ficha-obra" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-4xl bg-slate-800 border border-slate-700/50 shadow-2xl rounded-xl overflow-hidden transform transition-all scale-95 opacity-0 duration-300 flex flex-col" id="modal-ficha-obra-content" style="max-height: 90vh;">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50 flex-shrink-0 bg-slate-900 rounded-t-xl">
            <h3 class="text-lg text-slate-200 font-bold flex items-center gap-2" id="modal-title">
                <i class="fas fa-building text-green-400"></i>
                <span id="modal-title-text">Novo Projeto</span>
            </h3>
            <button type="button" onclick="fecharModal()" class="p-2 rounded-lg hover:bg-slate-700 transition text-slate-400">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Abas -->
        <div class="flex bg-slate-900/50 border-b border-slate-700 px-6 pt-2">
            <button type="button" onclick="switchTab('tab-gerais')" class="tab-btn active px-4 py-2 text-sm font-semibold text-slate-400 hover:text-indigo-400 transition" id="btn-tab-gerais">Dados Gerais</button>
            <button type="button" onclick="switchTab('tab-cronograma')" class="tab-btn px-4 py-2 text-sm font-semibold text-slate-400 hover:text-indigo-400 transition" id="btn-tab-cronograma">Cronograma</button>
            <button type="button" onclick="switchTab('tab-gestores')" class="tab-btn px-4 py-2 text-sm font-semibold text-slate-400 hover:text-indigo-400 transition" id="btn-tab-gestores">Gestores</button>
            <button type="button" onclick="switchTab('tab-financeiro')" class="tab-btn px-4 py-2 text-sm font-semibold text-slate-400 hover:text-indigo-400 transition" id="btn-tab-financeiro">Financeiro</button>
        </div>

        <!-- Body -->
        <div class="p-6 flex-1 overflow-y-auto">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <h4 class="font-bold">Atenção: Houve um erro ao salvar</h4>
                    </div>
                    <ul class="list-disc list-inside text-sm font-medium space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="form-obra" method="POST" action="{{ route('erp-obras-manual.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="form-method">
                
                {{-- ABA: DADOS GERAIS --}}
                <div id="tab-gerais" class="tab-content block space-y-4">
                    <!-- Linha 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Código do Cliente</label>
                            <input type="text" name="cliente_codigo" id="input-cliente-codigo" readonly class="w-full bg-slate-900 border border-slate-700 text-slate-500 rounded-lg p-2.5 outline-none cursor-not-allowed opacity-80" tabindex="-1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Código do Projeto *</label>
                            <input type="text" name="projeto_codigo" id="input-projeto-codigo" maxlength="8" required class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none uppercase">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nome do Projeto *</label>
                            <input type="hidden" name="projeto_nome" id="hidden-projeto-nome">
                            <input type="text" id="ui-nome-bloqueado" class="w-full bg-slate-900 border border-slate-700 text-slate-500 rounded-lg p-2.5 outline-none cursor-not-allowed hidden opacity-80" readonly tabindex="-1" placeholder="Nome automático pelo CNPJ">
                            <select id="ui-nome-select" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none hidden"></select>
                            <input type="text" id="ui-nome-livre" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Digite o nome do projeto...">
                        </div>
                    </div>

                    <!-- Linha 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 border-t border-slate-700 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Categoria</label>
                            <select name="tipo_categoria" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                                <option value="">Selecione...</option>
                                <option value="CONTRATO">CONTRATO</option>
                                <option value="PROPOSTA">PROPOSTA</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">CNPJ / CPF</label>
                            <input type="text" name="cnpj" id="input-cliente-cnpj" maxlength="18" oninput="mascaraCpfCnpj(this)" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Razão Social</label>
                            <input type="text" name="razao_social" id="input-razao-social" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Linha 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-700 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Unidade</label>
                            <input type="text" name="projeto_unidade" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Objeto</label>
                            <input type="text" name="projeto_objeto" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Linha 4 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-slate-700 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Cidade</label>
                            <input type="text" name="cidade" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Endereço</label>
                            <input type="text" name="endereco" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Linha 5 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-700 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Target</label>
                            <input type="month" name="target" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Setor</label>
                            <select id="select-setor_id" name="setor_id" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                                <option value="" data-nome="">Selecione...</option>
                                @foreach($setores as $setor)
                                    <option value="{{ $setor->id }}" data-nome="{{ strtoupper($setor->nome) }}">{{ $setor->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Linha 6 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-700 pt-4">
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-slate-900 border border-slate-700 rounded-lg hover:border-indigo-500 transition">
                                <input type="hidden" name="status_ativo" value="0">
                                <input type="checkbox" name="status_ativo" value="1" checked class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500">
                                <span class="text-sm font-bold text-slate-300">Obra Ativa</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-slate-900 border border-slate-700 rounded-lg hover:border-indigo-500 transition">
                                <input type="hidden" name="pedagio" value="0">
                                <input type="checkbox" name="pedagio" value="1" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500">
                                <span class="text-sm font-bold text-slate-300">Possui Pedágio</span>
                            </label>
                        </div>
                    </div>

                    <!-- Linha 7 -->
                    <div class="grid grid-cols-1 gap-4 border-t border-slate-700 pt-4">
                        <div class="col-span-full">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Comentários</label>
                            <textarea name="comentarios" rows="3" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- ABA: CRONOGRAMA --}}
                <div id="tab-cronograma" class="tab-content hidden space-y-4">
                    <!-- Linha 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                            <select name="projeto_status" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors appearance-none cursor-pointer">
                                <option value="">Selecione...</option>
                                <option value="CANCELADA">CANCELADA</option>
                                <option value="CONCLUIDA">CONCLUIDA</option>
                                <option value="EM ANDAMENTO">EM ANDAMENTO</option>
                                <option value="PENDENCIA DO CLIENTE">PENDENCIA DO CLIENTE</option>
                                <option value="PERMUTA">PERMUTA</option>
                                <option value="SUSPENSA">SUSPENSA</option>
                            </select>
                        </div>
                        <div id="wrapper-etapa" class="hidden">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Etapa(s)</label>
                            <div class="relative" data-multi-select data-summary-label="etapas selecionadas">
                                <!-- Caixa Principal (Simula o select fechado) -->
                                <div type="button" onclick="toggleMultiSelect(this)" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 cursor-pointer flex items-center justify-between text-slate-300 hover:border-slate-600 transition">
                                    <span class="text-sm truncate select-summary">Nenhum selecionado</span>
                                    <i class="fas fa-chevron-down text-xs text-slate-500 transition-transform duration-200"></i>
                                </div>

                                <!-- Caixa de Opções (Tamanho com scroll e opções customizadas) -->
                                <div class="hidden absolute top-full left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-lg shadow-2xl z-50 p-2 space-y-1 max-h-48 overflow-y-auto options-container" id="select-etapa-options">
                                    @if(isset($etapas))
                                        @foreach($etapas as $etapa)
                                            <label class="flex items-center justify-between px-3 py-1.5 rounded-md cursor-pointer transition-all text-slate-300 hover:bg-slate-800 option-item has-[:checked]:bg-indigo-500/20 has-[:checked]:border has-[:checked]:border-indigo-500/40 has-[:checked]:text-indigo-300 has-[:checked]:[&_.check-icon]:opacity-100" onclick="handleCheckboxClick(event, this)">
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" name="projeto_etapa[]" value="{{ $etapa->nome }}" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500 hidden checkbox-input" onchange="updateMultiSelectState(this)">
                                                    <span class="text-sm font-semibold">{{ $etapa->nome }}</span>
                                                </div>
                                                <i class="fas fa-check text-indigo-400 text-xs opacity-0 transition-opacity duration-200 check-icon"></i>
                                            </label>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Avanço do Projeto (%)</label>
                            <input type="number" step="0.01" min="0" max="100" oninput="this.value = Math.min(Math.max(this.value, 0), 100)" name="projeto_avanco" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Linha 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-700 pt-4">
                        <div class="md:col-span-2 mb-2">
                            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider">Período de Cronograma</h4>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Cronograma Inicial</label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" id="chk-ausente-inicial" name="ausencia_cronograma" value="1" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500 absence-toggle" data-targets="cronograma_inicio,cronograma_fim">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ausente</span>
                                </label>
                            </div>
                            <input type="date" name="cronograma_inicio" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Cronograma Final</label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" id="chk-ausente-final" value="1" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500 absence-toggle" data-targets="cronograma_inicio,cronograma_fim">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ausente</span>
                                </label>
                            </div>
                            <input type="date" name="cronograma_fim" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors">
                        </div>
                    </div>

                    <!-- Linha 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-700 pt-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Contrato (Assinatura)</label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="ausencia_contrato" value="1" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500 absence-toggle" data-targets="contrato_assinatura">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ausente</span>
                                </label>
                            </div>
                            <input type="date" name="contrato_assinatura" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Termo de Entrega</label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="ausencia_termo" value="1" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500 absence-toggle" data-targets="termo_entrega">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ausente</span>
                                </label>
                            </div>
                            <input type="date" name="termo_entrega" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors">
                        </div>
                    </div>
                </div>

                {{-- ABA: GESTORES --}}
                <div id="tab-gestores" class="tab-content hidden space-y-4">
                    @php
                        $cargos = [
                            'gerente_implantacao' => ['label' => 'Gerente de Implantação', 'options' => $gerentes, 'multiple' => false, 'id' => 'select-ger-imp'],
                            'coordenadores_implantacao[]' => ['label' => 'Coordenador(es) de Implantação', 'options' => $coordenadores, 'multiple' => true, 'id' => 'select-coord-imp'],
                            'gerente_manutencao' => ['label' => 'Gerente de Manutenção', 'options' => $gerentes, 'multiple' => false, 'id' => 'select-ger-man'],
                            'coordenadores_manutencao[]' => ['label' => 'Coordenador(es) de Manutenção', 'options' => $coordenadores, 'multiple' => true, 'id' => 'select-coord-man'],
                            'lider_comercial' => ['label' => 'Responsável Comercial', 'options' => $lideresComerciais, 'multiple' => false, 'id' => 'select-lider']
                        ];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($cargos as $campo => $dados)
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">{{ $dados['label'] }}</label>
                            @if($dados['multiple'])
                                <div class="relative" data-multi-select data-summary-label="coordenadores selecionados">
                                    <!-- Caixa Principal (Simula o select fechado com limite de 3 linhas visualmente) -->
                                    <div type="button" onclick="toggleMultiSelect(this)" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 cursor-pointer flex items-center justify-between text-slate-300 hover:border-slate-600 transition">
                                        <span class="text-sm truncate select-summary">Nenhum selecionado</span>
                                        <i class="fas fa-chevron-down text-xs text-slate-500 transition-transform duration-200"></i>
                                    </div>

                                    <!-- Caixa de Opções (Oculta por padrão, abre ao clicar, tamanho de 5 linhas com scroll) -->
                                    <div class="hidden absolute top-full left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-lg shadow-2xl z-50 p-2 space-y-1 max-h-48 overflow-y-auto options-container" id="{{ $dados['id'] ?? '' }}">
                                        @foreach($dados['options'] as $g)
                                            <label class="flex items-center justify-between px-3 py-1.5 rounded-md cursor-pointer transition-all text-slate-300 hover:bg-slate-800 option-item has-[:checked]:bg-indigo-500/20 has-[:checked]:border has-[:checked]:border-indigo-500/40 has-[:checked]:text-indigo-300 has-[:checked]:[&_.check-icon]:opacity-100" onclick="handleCheckboxClick(event, this)">
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" name="{{ $campo }}" value="{{ $g->id }}" class="rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500 hidden checkbox-input" onchange="updateMultiSelectState(this)">
                                                    <span class="text-sm font-semibold">{{ $g->nome_completo }}</span>
                                                </div>
                                                <i class="fas fa-check text-indigo-400 text-xs opacity-0 transition-opacity duration-200 check-icon"></i>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                {{-- Select normal para campos únicos (mantido igual) --}}
                                <select name="{{ $campo }}" id="{{ $dados['id'] ?? '' }}" class="w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors appearance-none cursor-pointer">
                                    <option value="">Nenhum...</option>
                                    @foreach($dados['options'] as $g)
                                        <option value="{{ $g->id }}">{{ $g->nome_completo }}</option>
                                    @endforeach
                                    @if($campo === 'lider_comercial')
                                        <option value="SAC">SAC</option>
                                    @endif
                                </select>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ABA: FINANCEIRO --}}
                <div id="tab-financeiro" class="tab-content hidden space-y-4">
                    @php
                        $financeiros = [
                            'valor_venda' => 'Valor de Venda',
                            'valor_monitoramento' => 'Monitoramento',
                            'valor_licenca' => 'Licença',
                            'valor_manutencao' => 'Manutenção',
                            'valor_locacao' => 'Locação'
                        ];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($financeiros as $campo => $label)
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">{{ $label }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-500 font-bold">R$</span>
                                <input type="text" name="{{ $campo }}" class="input-moeda w-full bg-slate-900 border border-slate-700 text-slate-300 rounded-lg p-2.5 pl-10 focus:ring-1 focus:ring-emerald-500 outline-none text-right" placeholder="0,00" onkeyup="mascaraMoeda(this, event)">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Rodapé Dinâmico -->
                <div class="mt-8 pt-6 border-t border-slate-700/50 flex justify-end gap-3">
                    <button type="button" onclick="fecharModal()" class="px-5 py-2 text-sm font-bold rounded-lg text-slate-400 hover:bg-slate-700 border border-slate-600 transition-colors">Cancelar</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow-lg shadow-indigo-900/40 transition-all flex items-center gap-2">
                        <i class="fas fa-save"></i> Salvar Obra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tabId) {
        // Esconder todos
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active', 'text-indigo-400'));
        
        // Mostrar ativo
        document.getElementById(tabId).classList.remove('hidden');
        const btnId = 'btn-' + tabId;
        document.getElementById(btnId).classList.add('active', 'text-indigo-400');
    }

    function formataParaRealJS(valorStr) {
        if (!valorStr) return '';
        const v = parseFloat(valorStr);
        if (isNaN(v)) return '';
        // 1234.56 -> 1.234,56
        return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function abrirModal(dados) {
        const modal = document.getElementById('modal-ficha-obra');
        const content = document.getElementById('modal-ficha-obra-content');
        const form = document.getElementById('form-obra');
        
        form.reset();
        switchTab('tab-gerais');

        if (dados) {
            document.getElementById('modal-title-text').innerText = 'Editar Projeto: ' + dados.projeto_codigo;
            form.action = `/erp-obras-manual/${dados.id}`;
            document.getElementById('form-method').value = 'PUT';
            
            // Popula os campos com nome coincidente
            Object.keys(dados).forEach(key => {
                // Tenta achar um checkbox primeiro, para evitar pegar o input hidden fallback
                let input = form.querySelector(`input[type="checkbox"][name="${key}"]`);
                
                if (input) {
                    input.checked = (dados[key] == 1 || dados[key] === true);
                } else {
                    input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        let val = dados[key];
                        // Se o valor for um objeto com id (relação Eloquent serializada), extrai o id
                        if (val !== null && typeof val === 'object' && 'id' in val) {
                            val = val.id;
                        }

                        // Tratar datas que vêm do BD truncando o datetime
                        if (input.type === 'date' && val) {
                            input.value = typeof val === 'string' ? val.substring(0, 10) : val;
                        } 
                        // Tratar input mês/ano (target) cortando no YYYY-MM
                        else if (input.type === 'month' && val) {
                            input.value = typeof val === 'string' ? val.substring(0, 7) : val;
                        }
                        // Tratar valores monetários aplicando a máscara initial
                        else if (input.classList.contains('input-moeda') && val) {
                            input.value = formataParaRealJS(val);
                        } 
                        else {
                            input.value = val !== null && val !== undefined ? val : '';
                        
                            // Reflete o valor do projeto_nome (hidden) no input livre se estiver em edição
                            if (key === 'projeto_nome') {
                                document.getElementById('ui-nome-bloqueado').classList.add('hidden');
                                document.getElementById('ui-nome-select').classList.add('hidden');
                                const inputLivre = document.getElementById('ui-nome-livre');
                                inputLivre.classList.remove('hidden');
                                inputLivre.value = val || '';
                            }
                        }
                    }
                }
            });

            // Garante atribuição precisa dos gestores (mesmo com serialização de objetos ou appends)
            const valLider = dados.lider_comercial_id ?? (dados.lider_comercial?.id ?? dados.lider_comercial);
            const valGerImp = dados.gerente_implantacao_id ?? (dados.gerente_implantacao?.id ?? dados.gerente_implantacao);
            const valGerMan = dados.gerente_manutencao_id ?? (dados.gerente_manutencao?.id ?? dados.gerente_manutencao);
            
            if (valLider !== undefined && valLider !== null) {
                const selLider = form.querySelector('[name="lider_comercial"]');
                if (selLider) selLider.value = valLider;
            }
            if (valGerImp !== undefined && valGerImp !== null) {
                const selGerImp = form.querySelector('[name="gerente_implantacao"]');
                if (selGerImp) selGerImp.value = valGerImp;
            }
            if (valGerMan !== undefined && valGerMan !== null) {
                const selGerMan = form.querySelector('[name="gerente_manutencao"]');
                if (selGerMan) selGerMan.value = valGerMan;
            }

            // Dispara mudança no select de setor para ajustar visibilidade da Etapa
            const selSetor = document.getElementById('select-setor_id');
            if (selSetor) {
                selSetor.dispatchEvent(new Event('change'));
            }

            // Popula os selects múltiplos de coordenadores
            const idsCoordenadores = (dados.coordenadores_projeto && Array.isArray(dados.coordenadores_projeto))
                ? dados.coordenadores_projeto.map(c => c.id.toString())
                : [];
            
            const setSelectMultiple = (containerId, values) => {
                const container = document.getElementById(containerId);
                if(container) {
                    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(chk => {
                        chk.checked = values.includes(chk.value);
                        updateMultiSelectState(chk); // Aplica o estilo e joga pro topo
                    });
                    updateSummary(container);
                }
            };

            setSelectMultiple('select-coord-imp', idsCoordenadores);
            setSelectMultiple('select-coord-man', idsCoordenadores);

            // Popula o select múltiplo de etapas (separadas por " - ")
            let etapasArray = [];
            if (dados.projeto_etapa && typeof dados.projeto_etapa === 'string') {
                etapasArray = dados.projeto_etapa.split(' - ').map(e => e.trim());
            } else if (Array.isArray(dados.projeto_etapa)) {
                etapasArray = dados.projeto_etapa;
            }
            setSelectMultiple('select-etapa-options', etapasArray);

            // Aplica estado visual dos checkboxes de ausência
            document.querySelectorAll('.absence-toggle').forEach(chk => {
                // Sincroniza o checkbox final (sem name) com o inicial
                if (chk.id === 'chk-ausente-final') {
                    const inicial = document.getElementById('chk-ausente-inicial');
                    if (inicial) chk.checked = inicial.checked;
                }
                
                if (window.handleAbsenceToggle) window.handleAbsenceToggle(chk);
            });

        } else {
            form.reset();
            document.getElementById('modal-title-text').innerText = 'Novo Projeto';
            form.action = `/erp-obras-manual`;
            document.getElementById('form-method').value = 'POST';
            form.querySelector('input[name="status_ativo"]').checked = true;
            
            // Reset visual dos nomes
            document.getElementById('ui-nome-bloqueado').classList.add('hidden');
            document.getElementById('ui-nome-bloqueado').value = '';
            document.getElementById('ui-nome-select').classList.add('hidden');
            document.getElementById('ui-nome-select').innerHTML = '';
            document.getElementById('ui-nome-livre').classList.remove('hidden');
            document.getElementById('ui-nome-livre').value = '';
            document.getElementById('hidden-projeto-nome').value = '';

            const inputRazao = document.getElementById('input-razao-social');
            if (inputRazao) {
                inputRazao.classList.remove('cursor-not-allowed', 'opacity-80', 'text-slate-500');
                inputRazao.classList.add('text-slate-300');
                inputRazao.readOnly = false;
                inputRazao.removeAttribute('tabindex');
            }

            // Reset dos selects múltiplos no Novo Projeto
            ['select-coord-imp', 'select-coord-man', 'select-etapa-options'].forEach(containerId => {
                const container = document.getElementById(containerId);
                if(container) {
                    container.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                        chk.checked = false;
                        updateMultiSelectState(chk);
                    });
                    updateSummary(container);
                }
            });

            const selSetor = document.getElementById('select-setor_id');
            if (selSetor) {
                selSetor.dispatchEvent(new Event('change'));
            }

            // Reseta checkboxes de ausência e aplica estado
            document.querySelectorAll('.absence-toggle').forEach(chk => {
                chk.checked = false;
                if (window.handleAbsenceToggle) window.handleAbsenceToggle(chk);
            });
        }

        // Abrir com animação
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function fecharModal() {
        const modal = document.getElementById('modal-ficha-obra');
        const content = document.getElementById('modal-ficha-obra-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function mascaraMoeda(i, e) {
        var v = i.value.replace(/\D/g,'');
        v = (v/100).toFixed(2) + '';
        v = v.replace(".", ",");
        v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
        
        // Se apagou tudo ou é zero
        if(v === "0,00" && e.key === "Backspace") {
            i.value = "";
            return;
        }
        
        i.value = v;
    }

    function mascaraCpfCnpj(input) {
        // Remove tudo o que não é dígito
        let value = input.value.replace(/\D/g, '');

        // Limita a 14 números (tamanho máximo de um CNPJ sem pontuação)
        if (value.length > 14) {
            value = value.substring(0, 14);
        }

        // Aplica máscara de CPF
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } 
        // Aplica máscara de CNPJ
        else {
            value = value.replace(/(\d{2})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1/$2');
            value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        }

        input.value = value;
    }

    // Variável para debounce
    let timeoutBusca;

    // --- AUTO EXTRAÇÃO DO CÓDIGO DO CLIENTE ---
    (function() {
        const inputProjeto = document.getElementById('input-projeto-codigo');
        const inputCliente = document.getElementById('input-cliente-codigo');

        function triggerBuscaDebounced() {
            clearTimeout(timeoutBusca);
            timeoutBusca = setTimeout(buscarSugestoesNome, 500);
        }

        if(inputProjeto && inputCliente) {
            // Escuta blur e input para melhor responsividade
            ['blur', 'input'].forEach(evento => {
                inputProjeto.addEventListener(evento, function() {
                    // Força maiúscula
                    this.value = this.value.toUpperCase();
                    
                    const val = this.value.trim();
                    if (val.length >= 5) {
                        inputCliente.value = val.substring(1, 5);
                        // O código do cliente já está formado (4 dígitos), podemos buscar
                        triggerBuscaDebounced();
                    } else {
                        inputCliente.value = '';
                    }
                });
            });
        }

        const inputCnpj = document.getElementById('input-cliente-cnpj');
        if(inputCnpj) {
            ['blur', 'input'].forEach(evento => {
                inputCnpj.addEventListener(evento, function() {
                    // Só dispara no input se tiver digitado ao menos o tamanho de um CPF
                    if (evento === 'blur' || this.value.length >= 14) {
                        triggerBuscaDebounced();
                    }
                });
            });
        }

        // Lógica para quando digitar no campo Livre
        const inputLivre = document.getElementById('ui-nome-livre');
        const hiddenNome = document.getElementById('hidden-projeto-nome');
        
        if (inputLivre && hiddenNome) {
            inputLivre.addEventListener('input', function() {
                hiddenNome.value = this.value;
            });
        }
    })();

    async function buscarSugestoesNome() {
        // Ignora busca se estiver editando uma obra já existente (modal PUT)
        if (document.getElementById('form-method').value === 'PUT') return;

        const clienteCodigo = document.getElementById('input-cliente-codigo').value;
        const cnpj = document.getElementById('input-cliente-cnpj').value;
        
        const uiBloqueado = document.getElementById('ui-nome-bloqueado');
        const uiSelect = document.getElementById('ui-nome-select');
        const uiLivre = document.getElementById('ui-nome-livre');
        const hiddenNome = document.getElementById('hidden-projeto-nome');
        const inputRazao = document.getElementById('input-razao-social');

        // Se não tiver pelo menos o clienteCodigo, volta pro estado livre limpo
        if (!clienteCodigo || clienteCodigo.length < 4) {
            uiBloqueado.classList.add('hidden');
            uiSelect.classList.add('hidden');
            uiLivre.classList.remove('hidden');
            if (inputRazao) {
                inputRazao.classList.remove('cursor-not-allowed', 'opacity-80', 'text-slate-500');
                inputRazao.classList.add('text-slate-300');
                inputRazao.readOnly = false;
                inputRazao.removeAttribute('tabindex');
            }
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = `/erp-obras-manual/verificar-cliente`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    cliente_codigo: clienteCodigo,
                    cnpj: cnpj
                })
            });
            const data = await response.json();

            // Reseta interfaces do nome
            uiBloqueado.classList.add('hidden');
            uiSelect.classList.add('hidden');
            uiLivre.classList.add('hidden');
            
            // --- Trava Global de Razão Social baseada no CNPJ ---
            if (inputRazao) {
                if (data.razao_social) {
                    inputRazao.value = data.razao_social;
                    inputRazao.classList.add('cursor-not-allowed', 'opacity-80', 'text-slate-500');
                    inputRazao.classList.remove('text-slate-300');
                    inputRazao.readOnly = true;
                    inputRazao.tabIndex = -1;
                } else {
                    inputRazao.classList.remove('cursor-not-allowed', 'opacity-80', 'text-slate-500');
                    inputRazao.classList.add('text-slate-300');
                    inputRazao.readOnly = false;
                    inputRazao.removeAttribute('tabindex');
                }
            }

            if (data.acao === 'travar') {
                // 1. MATCH EXATO (TRAVAR)
                uiBloqueado.value = data.nome || '';
                uiBloqueado.classList.remove('hidden');
                uiBloqueado.readOnly = true;
                hiddenNome.value = data.nome || '';
                
            } 
            else if (data.acao === 'sugerir' && data.sugestoes && data.sugestoes.length > 0) {
                // 2. TEM SUGESTÕES MAS NÃO É EXATO
                uiSelect.innerHTML = '<option value="">Selecione uma sugestão...</option>';
                data.sugestoes.forEach(nome => {
                    const opt = document.createElement('option');
                    opt.value = nome;
                    opt.textContent = nome;
                    uiSelect.appendChild(opt);
                });
                
                uiSelect.innerHTML += '<option value="outro" class="font-bold text-indigo-400">Outro (Digitar novo)</option>';
                
                uiSelect.classList.remove('hidden');
                hiddenNome.value = ''; // Exige escolha do usuário
                
                // Listener pro select
                uiSelect.onchange = function() {
                    if (this.value === 'outro') {
                        uiSelect.classList.add('hidden');
                        uiLivre.classList.remove('hidden');
                        uiLivre.value = '';
                        hiddenNome.value = '';
                        uiLivre.focus();
                    } else {
                        hiddenNome.value = this.value;
                    }
                };
            } 
            else {
                // 3. NOVO CADASTRO / SEM REFERÊNCIA (LIVRE)
                uiLivre.classList.remove('hidden');
                // Se já tinha digitado algo no input livre, mantem
                hiddenNome.value = uiLivre.value;
            }

        } catch (e) {
            console.error('Erro ao verificar cliente:', e);
            // Fallback
            uiBloqueado.classList.add('hidden');
            uiSelect.classList.add('hidden');
            uiLivre.classList.remove('hidden');
        }
    }

    // Regra de Visibilidade da Etapa baseada no Setor (Locação)
    (function() {
        const selectSetor = document.getElementById('select-setor_id');
        const wrapperEtapa = document.getElementById('wrapper-etapa');
        const containerEtapa = document.getElementById('select-etapa-options');

        function toggleEtapaVisibilidade() {
            if (!selectSetor) return;
            const selectedOption = selectSetor.options[selectSetor.selectedIndex];
            if (!selectedOption) return;
            
            const setorNome = selectedOption.getAttribute('data-nome');
            
            if (setorNome && setorNome.toUpperCase().includes('LOCA')) { // Verifica se tem LOCACAO no nome
                wrapperEtapa.classList.remove('hidden');
            } else {
                wrapperEtapa.classList.add('hidden');
                if (containerEtapa) {
                    containerEtapa.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                        chk.checked = false;
                        updateMultiSelectState(chk);
                    });
                    updateSummary(containerEtapa);
                }
            }
        }

        if (selectSetor) {
            selectSetor.addEventListener('change', toggleEtapaVisibilidade);
            // Também chama ao carregar a página caso seja um modal de edição sendo populado
            toggleEtapaVisibilidade();
            
            // Observer para quando o modal for aberto e o select preenchido (caso use Livewire/Alpine ou JS para popular)
            // Uma opção simples é interceptar a abertura do modal:
            window.addEventListener('modal-opened', toggleEtapaVisibilidade);
            
            // MutationObserver para garantir que reavalie se o JS preencher o value depois
            const observer = new MutationObserver(toggleEtapaVisibilidade);
            observer.observe(selectSetor, { attributes: true, attributeFilter: ['value'] });
        }
    })();

    // Abre/Fecha o dropdown ao clicar na caixa principal
    function toggleMultiSelect(element) {
        const parent = element.closest('[data-multi-select]');
        const dropdown = parent.querySelector('.options-container');
        const icon = element.querySelector('.fa-chevron-down');
        
        // Fecha os outros dropdowns que estiverem abertos
        document.querySelectorAll('.options-container').forEach(el => {
            if (el !== dropdown) el.classList.add('hidden');
        });
        document.querySelectorAll('[data-multi-select] .fa-chevron-down').forEach(el => {
            if (el !== icon) el.style.transform = 'rotate(0deg)';
        });

        dropdown.classList.toggle('hidden');
        icon.style.transform = dropdown.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    // Garante que o clique na linha altere o checkbox corretamente
    function handleCheckboxClick(event, label) {
        event.preventDefault(); // Evita comportamentos duplicados do navegador
        const checkbox = label.querySelector('.checkbox-input');
        checkbox.checked = !checkbox.checked;
        
        // Dispara o evento de mudança manualmente para atualizar estilos e posições
        checkbox.dispatchEvent(new Event('change'));
    }

    // Atualiza o estilo visual (azul dark com borda) e move para o topo
    function updateMultiSelectState(checkbox) {
        const label = checkbox.closest('.option-item');
        const container = checkbox.closest('.options-container');
        const checkIcon = label.querySelector('.check-icon');

        if (checkbox.checked) {
            label.classList.add('bg-indigo-500/20', 'border', 'border-indigo-500/40', 'text-indigo-300');
            label.classList.remove('text-slate-300', 'hover:bg-slate-800');
            if (checkIcon) {
                checkIcon.classList.remove('opacity-0', 'hidden');
                checkIcon.classList.add('opacity-100');
            }
        } else {
            label.classList.remove('bg-indigo-500/20', 'border', 'border-indigo-500/40', 'text-indigo-300');
            label.classList.add('text-slate-300', 'hover:bg-slate-800');
            if (checkIcon) {
                checkIcon.classList.remove('opacity-100');
                checkIcon.classList.add('opacity-0');
            }
        }

        sortOptionsToTop(container);
        updateSummary(container);
    }

    // Joga os itens selecionados para o topo da lista automaticamente
    function sortOptionsToTop(container) {
        const items = Array.from(container.querySelectorAll('.option-item'));
        items.sort((a, b) => {
            const aChecked = a.querySelector('input').checked ? 1 : 0;
            const bChecked = b.querySelector('input').checked ? 1 : 0;
            return bChecked - aChecked; // 1 vem antes de 0 (selecionados no topo)
        });
        items.forEach(item => container.appendChild(item));
    }

    // Atualiza o texto que aparece na caixinha fechada
    function updateSummary(container) {
        const parent = container.closest('[data-multi-select]');
        if (!parent) return;
        const summary = parent.querySelector('.select-summary');
        if (!summary) return;
        const checkedBoxes = container.querySelectorAll('input:checked');
        const summaryLabel = parent.getAttribute('data-summary-label') || 'coordenadores selecionados';

        if (checkedBoxes.length === 0) {
            summary.textContent = 'Nenhum selecionado';
        } else if (checkedBoxes.length === 1) {
            const name = checkedBoxes[0].closest('.option-item').querySelector('span').textContent;
            summary.textContent = name;
        } else {
            summary.textContent = `${checkedBoxes.length} ${summaryLabel}`;
        }
    }

    // Fecha o dropdown caso clique fora dele
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-multi-select]')) {
            document.querySelectorAll('.options-container').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[data-multi-select] .fa-chevron-down').forEach(el => el.style.transform = 'rotate(0deg)');
        }
    });

    // Lógica de Trava de Campos (Ausência)
    window.handleAbsenceToggle = function(checkbox) {
        const targets = checkbox.getAttribute('data-targets').split(',');
        const isAbsent = checkbox.checked;
        
        targets.forEach(targetName => {
            const targetInput = document.querySelector(`input[name="${targetName}"]`);
            if (targetInput) {
                if (isAbsent) {
                    targetInput.value = '';
                    targetInput.readOnly = true;
                    targetInput.classList.add('bg-slate-800', 'text-slate-500', 'cursor-not-allowed', 'opacity-70', 'pointer-events-none');
                    targetInput.classList.remove('bg-slate-900', 'text-slate-300');
                } else {
                    targetInput.readOnly = false;
                    targetInput.classList.remove('bg-slate-800', 'text-slate-500', 'cursor-not-allowed', 'opacity-70', 'pointer-events-none');
                    targetInput.classList.add('bg-slate-900', 'text-slate-300');
                }
            }
        });
    };

    (function() {
        // Sincronização dos checkboxes espelho de cronograma
        const chkAusenteInicial = document.getElementById('chk-ausente-inicial');
        const chkAusenteFinal = document.getElementById('chk-ausente-final');
        
        if (chkAusenteInicial && chkAusenteFinal) {
            chkAusenteInicial.addEventListener('change', function() {
                chkAusenteFinal.checked = this.checked;
                window.handleAbsenceToggle(this);
            });
            
            chkAusenteFinal.addEventListener('change', function() {
                chkAusenteInicial.checked = this.checked;
                window.handleAbsenceToggle(this);
            });
        }

        document.querySelectorAll('.absence-toggle').forEach(toggle => {
            // Ignorar os que já possuem listener customizado acima
            if (toggle.id !== 'chk-ausente-inicial' && toggle.id !== 'chk-ausente-final') {
                toggle.addEventListener('change', () => window.handleAbsenceToggle(toggle));
            }
        });

        @if($errors->any())
            // Reabre o modal em caso de erro de validação
            // Tenta manter o estado passando null para novo (limpa form) ou o objeto json se tiver id
            @if(old('id') || old('_method') === 'PUT')
                abrirModal({!! json_encode(old()) !!});
            @else
                abrirModal(null);
                // Opcional: Para evitar perda de dados no POST, poderíamos popular os inputs aqui
            @endif
        @endif
    })();
</script>
@endpush
