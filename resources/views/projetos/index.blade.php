@extends('layouts.app')

@section('title', 'Gestão de Projetos e Obras')

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
    title="Gestão de Projetos" 
    subtitle="Gerencie os projetos e atribua os gestores responsáveis"
    icon="fas fa-building text-purple-500"
    iconBg="from-purple-500 to-purple-700"
    backUrl="{{ route('painel') }}">
</x-page-header>
    
<div class="max-w-full xl:max-w-7xl mx-auto px-4 pb-4 pt-1 sm:px-6 sm:pb-6 sm:pt-1">

    <div class="flex items-center gap-2 sm:gap-4 relative justify-between mb-6">
            
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:flex-1">
            <div class="relative w-full sm:w-80 lg:w-96" id="searchContainerProjetos">
                <input type="text" id="searchInputProjetos" autocomplete="off" placeholder="Buscar projeto..." class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                
                <ul id="searchDropdownProjetos" class="hidden absolute top-full left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-slate-800 border border-slate-700 rounded-lg shadow-2xl z-50">
                    <!-- 1. Opções de busca por NOME (Garante que o nome apareça só uma vez) -->
                    @foreach($projetos->unique('nome') as $projeto)
                        <li class="px-4 py-2 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition dropdown-item flex items-center gap-2 group">
                            <i class="fas fa-user text-slate-500 group-hover:text-indigo-200 text-xs w-4 text-center"></i>
                            <span class="valor-filtro">{{ $projeto->nome }}</span>
                        </li>
                    @endforeach
                    
                    <!-- 2. Opções de busca por CÓDIGO (Lista todos os códigos existentes) -->
                    @foreach($projetos->unique('codigo') as $projeto)
                        <li class="px-4 py-2 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition dropdown-item flex items-center gap-2 group">
                            <i class="fas fa-building text-slate-500 group-hover:text-indigo-200 text-xs w-4 text-center"></i>
                            <span class="valor-filtro">{{ $projeto->codigo }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <form action="{{ route('projetos.sincronizar') }}" method="POST" class="flex-shrink-0">
            @csrf
            <button type="submit" class="px-3 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg shadow-lg shadow-emerald-900/20 transition-all flex items-center justify-center gap-2 text-sm flex-shrink-0">
                <i class="fas fa-sync-alt"></i> <span class="hidden sm:inline">Sincronizar ERP</span>
            </button>
        </form>
        
    </div>

    {{-- ============================================================
         TABELA DE PROJETOS
         ============================================================ --}}
    <div class="overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg">
        <table class="min-w-full divide-y divide-slate-700/50" id="tabela-projetos">
            <thead>
                <tr class="bg-slate-900/30">
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Código</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Nome da Obra</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Gestores Vinculados</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider sticky right-0 bg-slate-900 z-20 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.3)]">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($projetos as $projeto)
                <tr class="hover:bg-slate-800/50 transition group">
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap">{{ $projeto->codigo ?? '-' }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300">{{ $projeto->nome }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap">
                        @if(!$projeto->ativo)
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 border border-red-500/30 rounded text-xs">Inativo</span>
                        @else
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 border border-green-500/30 rounded text-xs">Ativo</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-sm text-slate-300">
                        <div class="flex flex-wrap gap-1">
                            @forelse($projeto->gestores as $gestor)
                                <span class="bg-indigo-500/20 text-indigo-400 text-[10px] px-2 py-1 rounded border border-indigo-500/30">
                                    {{ $gestor->nome_completo }}
                                </span>
                            @empty
                                <span class="text-slate-500 text-xs italic">Nenhum gestor</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="py-3 px-4 text-center sticky right-0 bg-slate-800 group-hover:bg-slate-700/80 z-10 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.3)] transition-colors">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" 
                                    onclick="abrirModalFichaObra(this)" 
                                    data-info="{{ json_encode($projeto) }}" 
                                    class="p-2 rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors"
                                    title="Ficha da Obra">
                                <i class="fas fa-edit text-blue-400 text-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-3 px-4 text-center text-sm text-slate-400">
                        Nenhum projeto encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginação -->
        @if($projetos->hasPages())
        <div class="p-4 border-t border-slate-700/50 bg-slate-800/50">
            {{ $projetos->links() }}
        </div>
        @endif
    </div>
</div>



{{-- ==========================================
     MODAL FICHA DA OBRA (EDIÇÃO)
     ========================================== --}}
<div id="modal-ficha-obra" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-2xl bg-slate-800 border border-slate-700/50 shadow-2xl rounded-xl overflow-y-auto transform transition-all scale-95 opacity-0 duration-300 flex flex-col" id="modal-ficha-obra-content" style="max-height: 90vh;">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/50 flex-shrink-0 bg-slate-900 rounded-t-xl">
            <h3 class="text-lg text-slate-200 font-bold flex items-center gap-2">
                <i class="fas fa-edit text-blue-400"></i>
                Ficha da Obra
            </h3>
            <button onclick="fecharModalFichaObra()" class="p-2 rounded-lg hover:bg-slate-700 transition text-slate-400">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="p-6 flex-1">
            <form id="form-ficha-obra" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1 md:col-span-2 relative group">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nome da Obra *</label>
                        <input type="text" name="nome" disabled required class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-not-allowed">
                    </div>

                    <div class="relative group">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Código</label>
                        <input type="text" name="codigo" disabled class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-not-allowed">
                    </div>

                    <div class="relative group">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                        <select name="ativo_manual" disabled class="w-full bg-slate-900/50 border border-slate-700 text-slate-400 rounded-lg p-3 pr-10 focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-not-allowed">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>

                    <div class="col-span-1 md:col-span-2 mt-4 border-t border-slate-700 pt-4 relative group block-gestores-ficha">
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Gestores da Obra</label>
                            <!-- Botão Lápis (Apenas na Ficha da Obra) -->
                            <button type="button" onclick="desbloquearCampo(this)" class="text-slate-500 hover:text-indigo-400 transition-colors btn-editar-gestores" title="Editar Gestores">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>

                        <!-- Container onde as linhas de select serão injetadas -->
                        <div id="gestores-container-ficha" class="space-y-2 mb-3">
                            <!-- Linhas dinâmicas entram aqui -->
                        </div>

                        <!-- Botão de Adicionar Nova Linha -->
                        <button type="button" onclick="adicionarLinhaGestor('ficha')" class="btn-add-gestor-linha w-full py-2 border border-dashed border-slate-600 text-slate-400 hover:text-indigo-400 hover:border-indigo-500 rounded-lg text-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-plus"></i> Adicionar Gestor
                        </button>
                    </div>
                </div>

                <!-- Rodapé Dinâmico -->
                <div id="rodape-edicao" class="hidden mt-8 pt-6 border-t border-slate-700/50 bg-slate-800/30 -mx-6 px-6 -mb-6 pb-6 rounded-b-xl">
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="fecharModalFichaObra()" class="px-5 py-3 text-sm font-bold rounded-lg text-slate-400 hover:bg-slate-700 border border-slate-600 transition-colors">Cancelar</button>
                        <button type="submit" class="px-6 py-3 text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow-[0_4px_15px_rgba(79,70,229,0.4)] transition-all flex items-center gap-2 transform hover:scale-105">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function desbloquearCampo(btnElement) {
        const wrapper = btnElement.closest('.group');
        
        // Lógica para o bloco de Gestores
        if (wrapper.classList.contains('block-gestores-ficha')) {
            const container = document.getElementById('gestores-container-ficha');
            const selects = container.querySelectorAll('select[name="gestores_ids[]"]');
            const btnAdd = wrapper.querySelector('.btn-add-gestor-linha');
            const btnsRemove = container.querySelectorAll('.btn-remover-linha');
            
            if (btnElement.classList.contains('text-slate-500')) {
                // Desbloqueando
                selects.forEach(s => {
                    s.disabled = false;
                    s.classList.remove('bg-slate-900/50', 'cursor-not-allowed', 'opacity-70');
                    s.classList.add('bg-slate-800');
                });
                btnsRemove.forEach(b => {
                    b.classList.remove('hidden');
                    b.classList.add('flex');
                });
                btnAdd.classList.remove('hidden');
                
                btnElement.classList.add('text-indigo-400', 'opacity-100');
                btnElement.classList.remove('text-slate-500', 'opacity-50');
                document.getElementById('rodape-edicao').classList.remove('hidden');
            } else {
                // Bloqueando novamente
                selects.forEach(s => {
                    s.disabled = true;
                    s.classList.add('bg-slate-900/50', 'cursor-not-allowed', 'opacity-70');
                    s.classList.remove('bg-slate-800');
                });
                btnsRemove.forEach(b => {
                    b.classList.add('hidden');
                    b.classList.remove('flex');
                });
                btnAdd.classList.add('hidden');
                
                btnElement.classList.remove('text-indigo-400', 'opacity-100');
                btnElement.classList.add('text-slate-500', 'opacity-50');
            }
            return;
        }


    }



    function abrirModalFichaObra(btnElement) {
        try {
            const dados = JSON.parse(btnElement.getAttribute('data-info'));
            const form = document.getElementById('form-ficha-obra');
            
            form.action = `/projetos/${dados.id}`;
            form.reset();

            const fields = ['nome', 'codigo'];
            fields.forEach(f => {
                if (form.elements[f]) {
                    form.elements[f].value = dados[f] || '';
                    form.elements[f].disabled = true;
                    form.elements[f].classList.add('bg-slate-900/50', 'text-slate-400');
                    form.elements[f].classList.remove('bg-slate-800', 'text-white');
                }
            });

            if (form.elements['ativo_manual']) {
                form.elements['ativo_manual'].value = dados.ativo ? '1' : '0';
                form.elements['ativo_manual'].disabled = true;
                form.elements['ativo_manual'].classList.add('bg-slate-900/50', 'text-slate-400');
                form.elements['ativo_manual'].classList.remove('bg-slate-800', 'text-white');
            }

            // Gestores da Obra (Linhas Dinâmicas)
            const containerFicha = document.getElementById('gestores-container-ficha');
            containerFicha.innerHTML = '';
            
            const btnAddGestor = document.querySelector('.block-gestores-ficha .btn-add-gestor-linha');
            if (btnAddGestor) btnAddGestor.classList.add('hidden');

            if (dados.gestores && dados.gestores.length > 0) {
                dados.gestores.forEach(g => {
                    adicionarLinhaGestor('ficha', g.id, true);
                });
            }

            // Resetar botoes
            form.querySelectorAll('.group button').forEach(b => {
                b.classList.remove('text-indigo-400', 'opacity-100');
                b.classList.add('text-slate-500', 'opacity-50');
            });
            
            document.getElementById('rodape-edicao').classList.add('hidden');

            // Abrir com animação
            const modal = document.getElementById('modal-ficha-obra');
            const content = document.getElementById('modal-ficha-obra-content');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
            
        } catch(e) {
            console.error("Erro ao abrir Ficha da Obra:", e);
        }
    }

    function fecharModalFichaObra() {
        const modal = document.getElementById('modal-ficha-obra');
        const content = document.getElementById('modal-ficha-obra-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // --- LÓGICA DE LINHAS DINÂMICAS DE GESTORES ---
    const possiveisGestores = @json($possiveisGestores);

    function adicionarLinhaGestor(tipoModal, gestorIdSelecionado = '', bloqueado = false) {
        const container = document.getElementById(tipoModal === 'novo' ? 'gestores-container-novo' : 'gestores-container-ficha');
        const linhaId = 'gestor_row_' + Date.now() + Math.floor(Math.random() * 1000);
        
        let optionsHTML = '<option value="" disabled selected>Selecione um gestor...</option>';
        possiveisGestores.forEach(g => {
            const selected = (g.id == gestorIdSelecionado) ? 'selected' : '';
            optionsHTML += `<option value="${g.id}" ${selected}>${g.nome_completo}</option>`;
        });

        const disabledAttr = bloqueado ? 'disabled' : '';
        const cursorClass = bloqueado ? 'cursor-not-allowed opacity-70 bg-slate-900/50' : 'bg-slate-800';
        const btnRemoveClass = bloqueado ? 'hidden' : 'flex';

        const html = `
            <div id="${linhaId}" class="flex items-center gap-2">
                <select name="gestores_ids[]" ${disabledAttr} required class="flex-1 h-[46px] px-3 border border-slate-700 text-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 transition-colors ${cursorClass}">
                    ${optionsHTML}
                </select>
                <button type="button" onclick="removerLinhaGestor('${linhaId}')" class="btn-remover-linha ${btnRemoveClass} items-center justify-center w-[46px] h-[46px] bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', html);
    }

    function removerLinhaGestor(linhaId) {
        document.getElementById(linhaId).remove();
    }

    // ==========================================
    // Autocomplete + Pesquisa Sob Demanda (Projetos)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const inputBusca = document.getElementById('searchInputProjetos');
        const dropdownBusca = document.getElementById('searchDropdownProjetos');
        const dropdownItems = dropdownBusca ? dropdownBusca.querySelectorAll('li') : [];
        const tabelaProjetos = document.getElementById('tabela-projetos');

        if (tabelaProjetos) {
            const linhasTabela = tabelaProjetos.querySelectorAll('tbody tr');

            function executarFiltro() {
                const termoBusca = inputBusca ? inputBusca.value.toLowerCase().trim() : '';

                linhasTabela.forEach(linha => {
                    if (linha.querySelector('td[colspan]')) return; // ignorar placeholder vazio

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
                    
                    // Apenas começa a buscar a partir do 2º caractere
                    if (query.length < 2) {
                        dropdownBusca.classList.add('hidden');
                        return;
                    }

                    let hasVisibleItems = false;
                    
                    dropdownItems.forEach(li => {
                        // Pega o texto exato daquela linha (seja o nome ou o código)
                        const textoItem = li.querySelector('.valor-filtro').textContent.toLowerCase();
                        
                        if (textoItem.includes(query)) {
                            li.style.display = 'flex'; // Exibe a linha
                            hasVisibleItems = true;
                        } else {
                            li.style.display = 'none'; // Esconde a linha
                        }
                    });

                    // Mostra a caixa do dropdown se achou algo, esconde se estiver vazia
                    if (hasVisibleItems) {
                        dropdownBusca.classList.remove('hidden');
                    } else {
                        dropdownBusca.classList.add('hidden');
                    }
                });

                dropdownItems.forEach(li => {
                    li.addEventListener('click', function() {
                        // Pega apenas o valor (nome ou código) que o usuário clicou
                        const valorClicado = this.querySelector('.valor-filtro').textContent.trim();
                        
                        inputBusca.value = valorClicado;
                        dropdownBusca.classList.add('hidden');
                        
                        // Roda o seu filtro da tabela com o valor que acabou de ser selecionado
                        executarFiltro();
                    });
                });
            }
        }
    });
</script>
@endpush
