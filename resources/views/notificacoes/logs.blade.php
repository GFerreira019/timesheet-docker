@extends('layouts.app')

@section('title', 'Logs de Notificações')

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
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

{{-- ============================================================
     HEADER — Padrão CONNECT (Seção 14.1)
     ============================================================ --}}
<x-page-header 
    title="Controle de Notificações" 
    subtitle="Controle de envio e recebimento de notificações."
    icon="fas fa-envelope"
    iconBg="from-emerald-500 to-emerald-700"
    backUrl="{{ route('timesheet.index') }}">
</x-page-header>

<div class="max-w-7xl mx-auto px-4 sm:px-6 mb-8">
    {{-- ============================================================
         Filtros
         ============================================================ --}}
    <div class="flex items-center justify-end gap-2 mb-2 fade-in">
        <div class="w-full md:justify-start md:flex items-center gap-2">
            @if(request('colaborador_id'))
                @foreach($colaboradores as $c)
                    @if($c->id == request('colaborador_id'))
                    <div class="flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-bold fade-in">
                        <span class="hidden sm:inline md:uppercase text-[9px] text-indigo-400/70">Destinatário:</span>
                        {{ $c->nome_completo }}
                    </div>
                    @endif
                @endforeach
            @endif

            @if(request('acao'))
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-teal-500/20 border border-teal-500/30 text-teal-300 text-xs font-bold fade-in">
                <span class="uppercase text-[9px] text-teal-400/70">Ação:</span>
                {{ request('acao') }}
            </div>
            @endif
            @if(request('status'))
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/20 border border-blue-500/30 text-blue-300 text-xs font-bold fade-in">
                <span class="uppercase text-[9px] text-blue-400/70">Status:</span>
                {{ request('status') == 'nao_lidas' ? 'Não Lidas' : 'Lidas' }}
            </div>
            @endif
        </div>
            
        @role('ADMIN')
        <button type="button" onclick="abrirModalPermissoes()" class="flex items-center gap-2 px-3 sm:px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-300 hover:text-white hover:border-emerald-500 transition h-[42px] shadow-sm">
            <i class="fas fa-cog text-emerald-400"></i>
            <span class="hidden sm:inline">Gerenciar Permissões</span>
        </button>
        @endrole

        <button onclick="document.getElementById('modalCalendarioLogs').classList.remove('hidden')" class="flex items-center gap-2 px-3 sm:px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-300 hover:text-white hover:border-indigo-500 transition h-[42px]">
            <i class="fas fa-calendar-alt text-indigo-400"></i>
            <span class="hidden sm:inline">{{ request('data') ? \Carbon\Carbon::parse(request('data'))->format('d/m/Y') : 'Filtrar Data' }}</span>
        </button>
        @if(request('data'))
        <a href="{{ request()->fullUrlWithQuery(['data' => null]) }}" class="flex items-center justify-center w-[42px] h-[42px] bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-red-400 hover:border-red-500/50 transition" title="Limpar Filtro de Data">
            <i class="fas fa-times"></i>
        </a>
        @endif
        
        <button type="button" onclick="abrirModalFiltros()" class="group relative bg-slate-800 hover:bg-slate-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-bold transition-all border border-slate-700 hover:border-slate-600 h-[42px] flex items-center gap-2 shadow-sm">
            <i class="fas fa-filter text-indigo-400 group-hover:text-white transition-colors"></i>
            <span class="hidden sm:inline">Filtrar</span>
            @if(request('colaborador_id') || request('acao') || request('status'))
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
            </span>
            @endif
        </button>

        @if(request('colaborador_id') || request('acao') || request('data') || request('status'))
        <a href="{{ route('notificacoes.logs') }}" class="bg-rose-900/20 hover:bg-rose-900/40 text-rose-400 hover:text-white p-2.5 rounded-lg border border-rose-900/30 hover:border-rose-500/50 transition-all h-[42px] w-[42px] flex items-center justify-center" title="Limpar Filtros">
            <i class="fas fa-times"></i>
        </a>
        @endif
    </div>
    
    {{-- ============================================================
         Cards Topo — Resumo
         ============================================================ --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 fade-in">
        {{-- Pendências --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-yellow-500/30 shadow-sm">
            <span class="text-xs text-yellow-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-clock text-yellow-400"></i>
                Pendências de Apontamento
            </span>
            <div class="text-2xl font-bold text-white mt-1">{{ $totalPendencias }} <span class="text-sm font-normal text-slate-500">notificações</span></div>
        </div>
        
        {{-- Aprovações --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-teal-500/30 shadow-sm">
            <span class="text-xs text-teal-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-check-double text-teal-400"></i>
                Aprovações Pendentes
            </span>
            <div class="text-2xl font-bold text-white mt-1">{{ $totalAprovacoes }} <span class="text-sm font-normal text-slate-500">notificações</span></div>
        </div>

        {{-- Recusados --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-red-500/30 shadow-sm">
            <span class="text-xs text-red-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-times text-red-400"></i>
                Apontamentos Recusados
            </span>
            <div class="text-2xl font-bold text-white mt-1">{{ $totalRecusados }} <span class="text-sm font-normal text-slate-500">notificações</span></div>
        </div>

        {{-- Não Lidas --}}
        <div class="bg-slate-800 p-4 rounded-xl border border-blue-500/30 shadow-sm">
            <span class="text-xs text-blue-400 font-bold uppercase flex items-center gap-2">
                <i class="fas fa-envelope text-blue-400"></i>
                Não Lidas
            </span>
            <div class="text-2xl font-bold text-white mt-1">{{ $totalNaoLidas }} <span class="text-sm font-normal text-slate-500">notificações</span></div>
        </div>
    </div>
</div>

{{-- Modal Filtros --}}
<div id="modal-filtros" class="relative z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 transition-opacity backdrop-blur-sm"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-slate-900 border border-slate-700 text-left shadow-2xl w-full max-w-lg fade-in">
                <div class="bg-slate-800 px-4 py-3 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-filter text-indigo-400"></i>
                        Filtrar Notificações
                    </h3>
                    <button type="button" onclick="fecharModalFiltros()" class="text-gray-400 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>
                <form method="GET" action="{{ route('notificacoes.logs') }}" class="p-6 space-y-5">
                    @if(request('data'))
                    <input type="hidden" name="data" value="{{ request('data') }}">
                    @endif
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Destinatário</label>
                        <div class="relative">
                            @php
                                $opcoesColabs = [];
                                foreach($colaboradores ?? [] as $c) {
                                    $opcoesColabs[$c->id] = $c->nome_completo . ' (' . ($c->cargo ?? 'Sem Cargo') . ')';
                                }
                            @endphp
                            <x-select2 
                                id="select-colaborador-notificacao" 
                                name="colaborador_id" 
                                placeholder="Filtrar por destinatário..." 
                                :options="$opcoesColabs" 
                                :selected="request('colaborador_id')"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Ação/Tipo</label>
                        <div class="relative">
                            <select name="acao" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                <option value="">-- Todas as Ações --</option>
                                @foreach($acoes as $acao)
                                <option value="{{ $acao }}" @if(request('acao') == $acao) selected @endif>{{ $acao }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1 ml-1">Status</label>
                        <div class="relative">
                            <select name="status" class="w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700">
                                <option value="">-- Todos os Status --</option>
                                <option value="nao_lidas" @if(request('status') == 'nao_lidas') selected @endif>Não Lidas</option>
                                <option value="lidas" @if(request('status') == 'lidas') selected @endif>Lidas</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end gap-3 border-t border-slate-800 mt-2">
                        <button type="button" onclick="fecharModalFiltros()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                            Cancelar
                        </button>
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

<div class="max-w-7xl mx-auto w-full px-4 sm:px-6">
    <div class="relative border-l-2 border-slate-700/50 ml-4 space-y-8 pb-12 mb-12">
        @forelse($logs as $index => $log)
        {{-- Card de Notificação na Linha do Tempo --}}
        <div class="relative pl-8 group fade-in" style="animation-delay: {{ ($index + 1) * 100 }}ms">
            
            {{-- Bolinha Conectora (Status de Leitura) --}}
            <div class="absolute -left-[9px] top-5 bg-slate-900 rounded-full p-1 border-2 z-10
                @if($log->lida) border-green-500 text-green-500
                @else border-slate-500 text-slate-500 @endif">
                
                @if($log->lida)
                    <i class="fas fa-envelope-open-text text-[10px]" title="Lida"></i>
                @else
                    <i class="fas fa-envelope text-[10px]" title="Não lida"></i>
                @endif
            </div>

            {{-- Card da Linha do Tempo (Padrão DS) --}}
            <div class="bg-slate-800 border border-slate-700/50 rounded-xl hover:border-slate-600 transition-all p-0 overflow-hidden group-hover:shadow-lg">
                
                {{-- Header do Card (Tipo + Título + Data/Hora) --}}
                <div class="px-5 py-3 border-b border-slate-700/50 flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-slate-900/20">
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- Badge de Tipo --}}
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider
                            @if($log->tipo == 'ALERTA') bg-yellow-500/20 text-yellow-500 border border-yellow-500/30
                            @elseif($log->tipo == 'SUCESSO') bg-green-500/20 text-green-500 border border-green-500/30
                            @else bg-blue-500/20 text-blue-400 border border-blue-500/30 @endif">
                            {{ $log->tipo }}
                        </span>
                        
                        {{-- Título da Notificação --}}
                        <span class="text-xs text-slate-400 font-bold flex items-center gap-1">
                            <i class="fas fa-cube text-slate-500 text-[10px]"></i>
                            {{ $log->titulo }}
                        </span>
                    </div>
                    
                    {{-- Lado Direito: Data/Hora --}}
                    <div class="text-right flex items-center gap-3">
                        <p class="text-xs text-slate-400 font-mono font-bold">
                            {{ \Carbon\Carbon::parse($log->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y') }} 
                            <span class="text-slate-600 mx-1">|</span> 
                            {{ \Carbon\Carbon::parse($log->created_at)->timezone('America/Sao_Paulo')->format('H:i:s') }}
                        </p>
                    </div>
                </div>
                
                {{-- Corpo do Card (Destinatário + Mensagem) --}}
                <div class="p-5 flex items-start gap-4">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0 mt-1">
                        @if($log->colaborador)
                            <div class="h-10 w-10 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300 border border-slate-600">
                                {{ strtoupper(substr($log->colaborador->nome_completo ?? 'XX', 0, 2)) }}
                            </div>
                        @else
                            <div class="h-10 w-10 rounded-full bg-slate-900 flex items-center justify-center text-slate-600 border border-slate-800 border-dashed">
                                <i class="fas fa-user-slash"></i>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Dados e Mensagem --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white mb-2">
                            @if($log->colaborador)
                                {{ $log->colaborador->nome_completo }}
                            @else
                                <span class="text-slate-500 italic">Destinatário Removido</span>
                            @endif
                        </p>
                        
                        {{-- Detalhes da Notificação (Estilo Console) --}}
                        <div class="bg-slate-950 rounded-lg p-3 border border-slate-700/50">
                            <p class="text-[13px] text-slate-300 leading-relaxed font-mono whitespace-pre-wrap">{!! nl2br(e($log->mensagem)) !!}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @empty
            <div class="pl-8 py-12 flex flex-col items-center justify-center text-slate-500 border border-dashed border-slate-700 rounded-xl bg-slate-800/30 mb-12">
                <i class="fas fa-search text-3xl mb-4 text-slate-600 block"></i>
                <p class="text-lg font-medium text-slate-400">Nenhuma notificação encontrada</p>
                <p class="text-sm text-slate-500 mt-1">Tente ajustar os filtros de data ou usuário.</p>
            </div>
        @endforelse
    </div>
    
    <div class="mt-4 flex justify-center">
        {{ $logs->links() }}
    </div>
</div>

{{-- ============================================================
     Modal Gerenciar Permissões
     ============================================================ --}}
<div id="modal-permissoes" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="fecharModalPermissoes()"></div>

        {{-- Modal Panel --}}
        <div class="inline-block align-bottom bg-slate-900 rounded-2xl text-left shadow-2xl shadow-black/50 transform transition-all sm:my-8 sm:align-middle max-w-2xl w-full border border-slate-700 flex flex-col max-h-[85vh]">
            <div class="px-6 pt-5 pb-4 border-b border-slate-700 shrink-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-bold text-white flex items-center gap-2" id="modal-title">
                        <i class="fas fa-cog text-emerald-400"></i>
                        Gerenciar Permissões
                    </h3>
                    <button type="button" onclick="fecharModalPermissoes()" class="text-slate-400 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    Avisos manuais sempre serão entregues. O Opt-out desativa apenas o envio massivo automático.
                </p>
                <div class="mt-4 relative">
                    <input type="text" id="searchInputPermissoes" autocomplete="off" placeholder="Buscar por colaborador ou cargo..." class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
                </div>
            </div>

            <div class="overflow-y-auto relative flex-1">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs text-slate-400 uppercase bg-slate-800/50 sticky top-0 z-10 backdrop-blur-sm shadow-sm">
                        <tr>
                            <th scope="col" class="px-4 py-3 rounded-l-lg">Colaborador</th>
                            <th scope="col" class="px-4 py-3">Cargo</th>
                            <th scope="col" class="px-4 py-3 rounded-r-lg w-32 text-center">Notificações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50" id="tabela-permissoes-body">
                        @foreach($colaboradoresPermissao as $colab)
                        <tr class="hover:bg-slate-800/30 transition-colors linha-colaborador" data-nome="{{ strtolower($colab->nome_completo) }}" data-cargo="{{ strtolower($colab->cargo ?? '') }}">
                            <td class="px-4 py-3 font-medium text-white">
                                {{ $colab->nome_completo }}
                            </td>
                            <td class="px-4 py-3 text-slate-400 text-xs">
                                {{ $colab->cargo ?? 'Sem Cargo' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer toggle-permissao" data-colaborador-id="{{ $colab->id }}" {{ $colab->recebe_notificacao ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-slate-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 flex justify-end gap-3 border-t border-slate-700 shrink-0">
                <button type="button" onclick="fecharModalPermissoes()" class="px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg hover:bg-slate-600 transition-colors text-sm border border-slate-600">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Lógica do Modal de Filtros
    const modalFiltros = document.getElementById('modal-filtros');

    function abrirModalFiltros() {
        modalFiltros.classList.remove('hidden');
    }

    function fecharModalFiltros() {
        modalFiltros.classList.add('hidden');
    }

    modalFiltros.addEventListener('click', function(e) {
        if (e.target === this.firstElementChild.nextElementSibling) {
            fecharModalFiltros();
        }
    });

    // Lógica do Modal de Permissões
    const modalPermissoes = document.getElementById('modal-permissoes');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function abrirModalPermissoes() {
        modalPermissoes.classList.remove('hidden');
    }

    function fecharModalPermissoes() {
        modalPermissoes.classList.add('hidden');
    }

    function showToast(message) {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-5 right-5 bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300';
        toast.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    $(document).ready(function() {
        // Dispara o submit automaticamente quando o usuário seleciona ou limpa um colaborador no Select2 de Filtros
        $('#select-colaborador-notificacao').on('change', function() {
            $(this).closest('form').submit();
        });

        // Atualizar permissão ao clicar no toggle da tabela
        document.querySelectorAll('.toggle-permissao').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const colabId = this.getAttribute('data-colaborador-id');
                const novoStatus = this.checked;

                if (!colabId) return;

                fetch(`{{ route('notificacoes.permissoes.toggle') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        colaborador_id: colabId,
                        recebe_notificacao: novoStatus
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                    } else {
                        throw new Error(data.message || 'Erro desconhecido');
                    }
                })
                .catch(err => {
                    console.error('Erro ao atualizar permissão:', err);
                    alert('Falha ao atualizar permissão. A página será atualizada.');
                    // Reverte visualmente em caso de erro
                    this.checked = !novoStatus;
                });
            });
        });

        // Barra de Pesquisa do Modal
        const inputBusca = document.getElementById('searchInputPermissoes');
        const linhasTabela = document.querySelectorAll('.linha-colaborador');

        if (inputBusca) {
            inputBusca.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();

                linhasTabela.forEach(linha => {
                    const nome = linha.getAttribute('data-nome') || '';
                    const cargo = linha.getAttribute('data-cargo') || '';

                    if (nome.includes(query) || cargo.includes(query)) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endpush

<x-modal-calendario 
    id="modalCalendarioLogs" 
    titulo="Filtrar Logs por Data" 
    rotaFiltro="{{ route('notificacoes.logs') }}" 
/>

@endsection
