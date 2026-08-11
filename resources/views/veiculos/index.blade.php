@extends('layouts.app')

@section('title', 'Gestão de Veículos')

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
    title="Veículos" 
    subtitle="Gestão de frota e status"
    icon="fas fa-car text-teal-500"
    iconBg="from-teal-500 to-teal-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto px-4 pb-4 pt-1 sm:px-6 sm:pb-6 sm:pt-1">

    <div class="flex items-center gap-4 sm:gap-4 relative justify-between -mb-6">
        
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:flex-1">
            <div class="relative w-full sm:w-80 lg:w-96" id="searchContainerVeiculos">
                <input type="text" id="searchInputVeiculos" autocomplete="off" placeholder="Buscar por placa ou descrição..." class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                
                <ul id="searchDropdownVeiculos" class="hidden absolute top-full left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-slate-800 border border-slate-700 rounded-lg shadow-2xl z-50">
                    @foreach($veiculos as $veiculo)
                        <li class="px-4 py-2 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition dropdown-item flex items-center gap-2 group" data-busca="{{ $veiculo->placa }}">
                            <i class="fas fa-car text-slate-500 group-hover:text-indigo-200 text-xs w-4 text-center"></i>
                            <span class="valor-filtro">{{ $veiculo->descricao }} - {{ $veiculo->placa }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <button type="button" onclick="abrirModalNovo()" class="flex-shrink-0 px-3 sm:px-4 py-2 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-lg shadow-lg shadow-teal-900/20 transition-all flex items-center gap-2 text-sm">
            <i class="fas fa-plus"></i> <span class="hidden sm:inline">Novo Veículo</span>
        </button>
        
    </div>
</div>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6 mt-4">
    <div class="overflow-x-auto bg-slate-800 rounded-xl border border-slate-700/50 shadow-lg">
        <table class="min-w-full divide-y divide-slate-700/50" id="tabela-veiculos">
            <thead>
                <tr class="bg-slate-900/30">
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Placa</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Modelo</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Rastreamento</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($veiculos as $veiculo)
                <tr class="hover:bg-slate-800/50 transition group">
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap align-middle font-mono font-bold">{{ $veiculo->placa }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-normal break-words align-middle">{{ $veiculo->descricao ?? '-' }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap align-middle">{{ $veiculo->sistema_rastreamento ?? '-' }}</td>
                    <td class="py-3 px-4 text-sm text-slate-300 whitespace-nowrap align-middle">
                        @if($veiculo->status === 'ativo')
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 border border-green-500/30 rounded text-xs">Ativo</span>
                        @else
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 border border-red-500/30 rounded text-xs">Inativo</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center whitespace-nowrap align-middle">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Botão de Editar -->
                            <button type="button" 
                                    onclick="abrirModalEditar({{ json_encode($veiculo) }})" 
                                    class="text-indigo-400 hover:text-indigo-300 transition p-2" 
                                    title="Editar Veículo">
                                <i class="fas fa-edit text-lg"></i>
                            </button>

                            <form action="{{ route('veiculos.toggleStatus', $veiculo->id) }}" method="POST" class="inline-block m-0">
                                @csrf
                                <button type="submit" 
                                        class="p-2 rounded-md transition-colors flex items-center justify-center {{ $veiculo->status === 'ativo' ? 'text-red-400 hover:bg-red-500 hover:text-white' : 'text-green-400 hover:bg-green-500 hover:text-white' }}"
                                        title="{{ $veiculo->status === 'ativo' ? 'Inativar Veículo' : 'Ativar Veículo' }}">
                                    
                                    @if($veiculo->status === 'ativo')
                                        <!-- Ícone empilhado: Carro + Proibido (Aparece quando está ativo para poder inativar) -->
                                        <span class="fa-stack text-[0.85rem]" style="vertical-align: top;">
                                            <!-- Ajustei o tamanho do carro levemente para encaixar melhor no símbolo de bloqueio -->
                                            <i class="fas fa-car fa-stack-1x transform -translate-x-0.55 fill-current text-gray-400"></i>
                                            <i class="fas fa-ban fa-stack-2x"></i>
                                        </span>
                                    @else
                                        <!-- Ícone de Ativação -->
                                        <i class="fas fa-check-circle text-xl"></i>
                                    @endif
                                    
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-3 px-4 text-center text-sm text-slate-400">
                        Nenhum veículo encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($veiculos->hasPages())
    <div class="mt-4">
        {{ $veiculos->links() }}
    </div>
    @endif
</div>

{{-- MODAL NOVO VEÍCULO --}}
<div id="modal-novo-veiculo" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-plus text-indigo-400"></i>
                        Cadastrar Veículo
                    </h3>
                    <button type="button" onclick="fecharModalNovo()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form method="POST" action="{{ route('veiculos.store') }}" class="p-6">
                    @csrf
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Placa *</label>
                            <input type="text" name="placa" maxlength="7" pattern="[A-Z]{3}[0-9][A-Z0-9][0-9]{2}" title="Digite uma placa válida (ex: ABC1234 ou ABC1D23)" placeholder="ABC1234" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all uppercase">
                        </div>
                        <div class="relative custom-autocomplete-container mb-4">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Modelo</label>
                            <input type="text" name="descricao" autocomplete="off" placeholder="Ex: HB20, GOL..." class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all autocomplete-input">
                            
                            <!-- Lista Flutuante Customizada -->
                            <ul class="hidden absolute z-50 w-full mt-1 bg-slate-700 border border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto autocomplete-list">
                                @if(isset($descricoes))
                                    @foreach($descricoes as $desc)
                                        <li class="px-4 py-3 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition border-b border-slate-600/50 last:border-0">{{ $desc }}</li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                        <div class="relative custom-autocomplete-container mb-4">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Sistema de Rastreamento</label>
                            <input type="text" name="sistema_rastreamento" autocomplete="off" placeholder="Ex: Sascar, Omnilink..." class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all autocomplete-input">
                            
                            <!-- Lista Flutuante Customizada -->
                            <ul class="hidden absolute z-50 w-full mt-1 bg-slate-700 border border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto autocomplete-list">
                                @if(isset($sistemasRastreamento))
                                    @foreach($sistemasRastreamento as $sistema)
                                        <li class="px-4 py-3 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition border-b border-slate-600/50 last:border-0">{{ $sistema }}</li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="pt-6 flex justify-end gap-3 border-t border-slate-800 mt-6">
                        <button type="button" onclick="fecharModalNovo()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all text-sm flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Salvar Veículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDITAR VEÍCULO --}}
<div id="modal-editar-veiculo" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-indigo-400"></i>
                        Editar Veículo
                    </h3>
                    <button type="button" onclick="fecharModalEditar()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form id="form-editar-veiculo" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_veiculo_id">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Placa *</label>
                            <input type="text" name="placa" id="edit_placa" maxlength="7" pattern="[A-Z]{3}[0-9][A-Z0-9][0-9]{2}" title="Digite uma placa válida (ex: ABC1234 ou ABC1D23)" placeholder="ABC1234" required class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all uppercase">
                        </div>
                        <div class="relative custom-autocomplete-container mb-4">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Modelo</label>
                            <input type="text" name="descricao" id="edit_descricao" autocomplete="off" placeholder="Ex: HB20, GOL..." class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all autocomplete-input">
                            
                            <!-- Lista Flutuante Customizada -->
                            <ul class="hidden absolute z-50 w-full mt-1 bg-slate-700 border border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto autocomplete-list">
                                @if(isset($descricoes))
                                    @foreach($descricoes as $desc)
                                        <li class="px-4 py-3 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition border-b border-slate-600/50 last:border-0">{{ $desc }}</li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                        <div class="relative custom-autocomplete-container mb-4">
                            <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Sistema de Rastreamento</label>
                            <input type="text" name="sistema_rastreamento" id="edit_sistema_rastreamento" autocomplete="off" placeholder="Ex: Sascar, Omnilink..." class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all autocomplete-input">
                            
                            <!-- Lista Flutuante Customizada -->
                            <ul class="hidden absolute z-50 w-full mt-1 bg-slate-700 border border-slate-600 rounded-lg shadow-xl max-h-48 overflow-y-auto autocomplete-list">
                                @if(isset($sistemasRastreamento))
                                    @foreach($sistemasRastreamento as $sistema)
                                        <li class="px-4 py-3 text-sm text-slate-300 hover:bg-indigo-600 hover:text-white cursor-pointer transition border-b border-slate-600/50 last:border-0">{{ $sistema }}</li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="pt-6 flex justify-end gap-3 border-t border-slate-800 mt-6">
                        <button type="button" onclick="fecharModalEditar()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-lg shadow-indigo-900/20 transition-all text-sm flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Atualizar Veículo
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
        document.getElementById('modal-novo-veiculo').classList.remove('hidden');
    }

    function fecharModalNovo() {
        document.getElementById('modal-novo-veiculo').classList.add('hidden');
    }

    function abrirModalEditar(veiculo) {
        document.getElementById('edit_veiculo_id').value = veiculo.id;
        document.getElementById('edit_placa').value = veiculo.placa;
        document.getElementById('edit_descricao').value = veiculo.descricao || '';
        document.getElementById('edit_sistema_rastreamento').value = veiculo.sistema_rastreamento || '';
        
        const form = document.getElementById('form-editar-veiculo');
        form.action = `/veiculos/${veiculo.id}`;
        
        document.getElementById('modal-editar-veiculo').classList.remove('hidden');
    }

    function fecharModalEditar() {
        document.getElementById('modal-editar-veiculo').classList.add('hidden');
    }

    // Autocomplete + Pesquisa Sob Demanda
    document.addEventListener('DOMContentLoaded', function() {
        const inputBusca = document.getElementById('searchInputVeiculos');
        const dropdownBusca = document.getElementById('searchDropdownVeiculos');
        const dropdownItems = dropdownBusca ? dropdownBusca.querySelectorAll('li') : [];
        const tabela = document.getElementById('tabela-veiculos');

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
                    
                    if (query.length < 2) {
                        dropdownBusca.classList.add('hidden');
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
                        // AQUI: Pega apenas a placa do atributo oculto, ignorando o " - "
                        const valorBusca = this.getAttribute('data-busca');
                        
                        inputBusca.value = valorBusca;
                        dropdownBusca.classList.add('hidden');
                        
                        // Opção A: Executa o filtro de tela (Rápido, sem mudar URL)
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

    document.addEventListener('input', function (e) {
        if ((e.target.closest('#modal-novo-veiculo') || e.target.closest('#modal-editar-veiculo')) && e.target.tagName === 'INPUT' && e.target.type === 'text') {
            // Guarda a posição do cursor para não pular para o final ao digitar
            let start = e.target.selectionStart;
            let end = e.target.selectionEnd;
            
            // Remove acentos e converte para maiúsculo
            e.target.value = e.target.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
            
            // Restaura o cursor
            e.target.setSelectionRange(start, end);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const autocompleteContainers = document.querySelectorAll('.custom-autocomplete-container');
    
        autocompleteContainers.forEach(container => {
            const input = container.querySelector('.autocomplete-input');
            const list = container.querySelector('.autocomplete-list');
            const items = list.querySelectorAll('li');
    
            if (!input || !list) return;
    
            // Mostra a lista ao focar no campo
            input.addEventListener('focus', function() {
                if (items.length > 0) {
                    list.classList.remove('hidden');
                    items.forEach(item => item.style.display = 'block'); // Reseta a busca
                }
            });
    
            // Filtra os itens ao digitar
            input.addEventListener('input', function() {
                const val = this.value.toUpperCase(); // O script anterior já converte o input, mas garantimos aqui
                let hasVisible = false;
    
                items.forEach(item => {
                    if (item.textContent.toUpperCase().includes(val)) {
                        item.style.display = 'block';
                        hasVisible = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
    
                if (hasVisible) {
                    list.classList.remove('hidden');
                } else {
                    list.classList.add('hidden');
                }
            });
    
            // Preenche o input ao clicar numa opção
            items.forEach(item => {
                // Usamos mousedown pois ocorre antes do blur do input
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault(); 
                    input.value = this.textContent;
                    list.classList.add('hidden');
                    // Dispara o evento input manualmente caso outros scripts dependam disso
                    input.dispatchEvent(new Event('input'));
                });
            });
    
            // Esconde a lista ao clicar fora (blur)
            input.addEventListener('blur', function() {
                list.classList.add('hidden');
            });
        });
    });
</script>
@endpush
