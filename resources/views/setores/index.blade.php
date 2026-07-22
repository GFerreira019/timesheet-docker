@extends('layouts.app')

@section('title', 'Gestão de Setores')

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
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    title="Setores" 
    subtitle="Gestão de departamentos"
    icon="fas fa-layer-group text-cyan-500"
    iconBg="from-cyan-500 to-cyan-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto px-4 pb-4 pt-1 sm:px-6 sm:pb-6 sm:pt-1">

    <div class="flex items-center gap-4 sm:gap-4 relative justify-between -mb-6">
        
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:flex-1">
            <div class="relative w-full sm:w-80 lg:w-96" id="searchContainerSetores">
                <input type="text" id="searchInputSetores" autocomplete="off" placeholder="Buscar por nome..." class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                
                <ul id="searchDropdownSetores" class="hidden absolute top-full left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-slate-800 border border-slate-700 rounded-lg shadow-2xl z-50">
                    @foreach($setores as $setor)
                        <li class="px-4 py-2 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition dropdown-item flex items-center gap-2 group" data-busca="{{ $setor->nome }}">
                            <i class="fas fa-layer-group text-slate-500 group-hover:text-indigo-200 text-xs w-4 text-center"></i>
                            <span class="valor-filtro">{{ $setor->nome }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <button type="button" onclick="abrirModalNovo()" class="flex-shrink-0 px-3 sm:px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all flex items-center gap-2 text-sm">
            <i class="fas fa-plus"></i> <span class="hidden sm:inline">Novo Setor</span>
        </button>
        
    </div>
</div>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6 mt-4">
    <div class="overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg">
        <table class="min-w-full divide-y divide-slate-700/50" id="tabela-setores">
            <thead>
                <tr class="bg-slate-900/30">
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider w-20">ID</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Nome</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider w-32">Status</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider w-32">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($setores as $setor)
                <tr class="hover:bg-slate-800/50 transition group">
                    <td class="py-3 px-4 text-sm text-slate-400 font-mono">{{ str_pad($setor->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 font-semibold">{{ $setor->nome }}</td>
                    <td class="py-3 px-4 text-sm whitespace-nowrap">
                        @if($setor->ativo)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 border border-green-500/30 rounded text-xs">Ativo</span>
                        @else
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 border border-red-500/30 rounded text-xs">Inativo</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" 
                                    onclick="abrirModalEditar({{ json_encode($setor) }})" 
                                    class="text-indigo-400 hover:text-indigo-300 transition p-2" 
                                    title="Editar Setor">
                                <i class="fas fa-edit text-lg"></i>
                            </button>

                            <form action="{{ route('setores.toggleStatus', $setor->id) }}" method="POST" class="inline-block m-0">
                                @csrf
                                <button type="submit" 
                                        class="p-2 rounded-md transition-colors flex items-center justify-center {{ $setor->ativo ? 'text-red-400 hover:bg-red-500 hover:text-white' : 'text-green-400 hover:bg-green-500 hover:text-white' }}"
                                        title="{{ $setor->ativo ? 'Inativar Setor' : 'Ativar Setor' }}">
                                    @if($setor->ativo)
                                        <i class="fas fa-ban text-lg"></i>
                                    @else
                                        <i class="fas fa-check-circle text-lg"></i>
                                    @endif
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-6 px-4 text-center text-sm text-slate-400">
                        Nenhum setor encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL NOVO SETOR --}}
<div id="modal-novo-setor" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-plus text-indigo-400"></i>
                        Cadastrar Setor
                    </h3>
                    <button type="button" onclick="fecharModalNovo()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form method="POST" action="{{ route('setores.store') }}" class="p-6">
                    @csrf
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Nome *</label>
                            <input type="text" name="nome" placeholder="Ex: ENGENHARIA" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all uppercase">
                        </div>
                    </div>
                    <div class="pt-6 flex justify-end gap-3 border-t border-slate-800 mt-6">
                        <button type="button" onclick="fecharModalNovo()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all text-sm flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Salvar Setor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDITAR SETOR --}}
<div id="modal-editar-setor" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-indigo-400"></i>
                        Editar Setor
                    </h3>
                    <button type="button" onclick="fecharModalEditar()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form id="form-editar-setor" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_setor_id">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Nome *</label>
                            <input type="text" name="nome" id="edit_nome" placeholder="Ex: ENGENHARIA" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all uppercase">
                        </div>
                    </div>
                    <div class="pt-6 flex justify-end gap-3 border-t border-slate-800 mt-6">
                        <button type="button" onclick="fecharModalEditar()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all text-sm flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Atualizar Setor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function abrirModalNovo() {
        document.getElementById('modal-novo-setor').classList.remove('hidden');
    }

    function fecharModalNovo() {
        document.getElementById('modal-novo-setor').classList.add('hidden');
    }

    function abrirModalEditar(setor) {
        document.getElementById('edit_setor_id').value = setor.id;
        document.getElementById('edit_nome').value = setor.nome;
        
        const form = document.getElementById('form-editar-setor');
        form.action = `/setores/${setor.id}`;
        
        document.getElementById('modal-editar-setor').classList.remove('hidden');
    }

    function fecharModalEditar() {
        document.getElementById('modal-editar-setor').classList.add('hidden');
    }

    // Filtro rápido de tabela
    document.addEventListener('DOMContentLoaded', function() {
        const inputBusca = document.getElementById('searchInputSetores');
        const dropdownBusca = document.getElementById('searchDropdownSetores');
        const dropdownItems = dropdownBusca ? dropdownBusca.querySelectorAll('li') : [];
        const tabela = document.getElementById('tabela-setores');

        if (tabela) {
            const linhasTabela = tabela.querySelectorAll('tbody tr');

            function executarFiltro() {
                const termoBusca = inputBusca ? inputBusca.value.toLowerCase().trim() : '';

                linhasTabela.forEach(linha => {
                    if (linha.querySelector('td[colspan]')) return; 

                    const textoLinha = linha.textContent.toLowerCase();
                    if (textoLinha.includes(termoBusca)) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                });
            }

            if (inputBusca && dropdownBusca) {
                inputBusca.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    
                    if (query.length < 1) {
                        dropdownBusca.classList.add('hidden');
                        executarFiltro();
                        return;
                    }

                    let hasVisibleItems = false;
                    
                    dropdownItems.forEach(li => {
                        const textoItem = li.querySelector('.valor-filtro').textContent.toLowerCase();
                        
                        if (textoItem.includes(query)) {
                            li.style.display = 'flex';
                            hasVisibleItems = true;
                        } else {
                            li.style.display = 'none';
                        }
                    });

                    if (hasVisibleItems) {
                        dropdownBusca.classList.remove('hidden');
                    } else {
                        dropdownBusca.classList.add('hidden');
                    }
                });

                dropdownItems.forEach(li => {
                    li.addEventListener('click', function() {
                        const valorBusca = this.getAttribute('data-busca');
                        inputBusca.value = valorBusca;
                        dropdownBusca.classList.add('hidden');
                        executarFiltro();
                    });
                });

                inputBusca.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        dropdownBusca.classList.add('hidden');
                        executarFiltro();
                    }
                });

                document.addEventListener('click', (e) => {
                    if (!inputBusca.contains(e.target) && !dropdownBusca.contains(e.target)) {
                        dropdownBusca.classList.add('hidden');
                    }
                });
            }
        }
    });

    // Força input maiúsculo e sem acentos (opcional para manter consistência)
    document.addEventListener('input', function (e) {
        if ((e.target.closest('#modal-novo-setor') || e.target.closest('#modal-editar-setor')) && e.target.tagName === 'INPUT' && e.target.type === 'text') {
            let start = e.target.selectionStart;
            let end = e.target.selectionEnd;
            e.target.value = e.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
            e.target.setSelectionRange(start, end);
        }
    });
</script>
@endpush
