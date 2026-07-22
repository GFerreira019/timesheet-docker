@php
    $count = $notificacoes_nao_lidas_count ?? 0;
    $notificacoes = $notificacoes_usuario ?? [];
@endphp

<div class="relative" id="notificacoes-bell-container">
    {{-- Botão do Sino --}}
    <button type="button" id="btn-notificacoes-bell" class="relative flex items-center justify-center w-11 h-11 bg-slate-800/80 border border-slate-700 hover:bg-slate-700 text-gray-400 hover:text-white rounded-full transition-all shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        
        {{-- Badge (indicador vermelho) --}}
        @if($count > 0)
            <span id="notificacoes-badge" class="absolute top-1 right-1.5 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-slate-900"></span>
            </span>
        @endif
    </button>

    {{-- Dropdown Modal --}}
    <div id="notificacoes-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden transform origin-top-right transition-all">
        
        <div class="px-4 py-3 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
            <h3 class="text-sm font-bold text-white tracking-wide">Notificações</h3>
            @if($count > 0)
                <span id="notificacoes-count-text" class="text-xs font-semibold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full">{{ $count }} não lidas</span>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto" id="notificacoes-list">
            @forelse($notificacoes as $notif)
                <div class="notificacao-item p-4 border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors {{ $notif->lida ? 'opacity-60' : '' }}" data-id="{{ $notif->id }}">
                    
                    <div class="flex justify-between items-start mb-1">
                        <p class="text-xs font-bold {{ $notif->tipo === 'ALERTA' ? 'text-amber-400' : 'text-indigo-400' }}">
                            @if($notif->tipo === 'ALERTA') <i class="fas fa-exclamation-triangle mr-1"></i> @endif
                            {{ $notif->titulo }}
                        </p>
                        <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ $notif->created_at?->diffForHumans() }}</span>
                    </div>
                    
                    <p class="text-xs text-gray-300 mb-2 leading-relaxed">{{ $notif->mensagem }}</p>
                    
                    {{-- Ações: Responder ou Marcar Lida --}}
                    @if(!$notif->lida)
                        @if($notif->tipo === 'ALERTA' && empty($notif->comentario_colaborador))
                            {{-- Input de Resposta --}}
                            <div class="mt-2 flex gap-2 responder-form">
                                <input type="text" class="form-input text-xs py-1.5 px-2 bg-slate-900 border-slate-600 focus:ring-amber-500 focus:border-amber-500 resposta-input" placeholder="Responder..." required>
                                <button type="button" class="btn-responder bg-amber-600 hover:bg-amber-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap" data-id="{{ $notif->id }}">
                                    Enviar
                                </button>
                            </div>
                            <div class="resposta-loading hidden mt-2 text-[10px] text-amber-400 flex items-center gap-1">
                                <i class="fas fa-circle-notch fa-spin"></i> Enviando...
                            </div>
                        @else
                            {{-- Botão Marcar como Lida --}}
                            <div class="mt-2 flex justify-end">
                                <button type="button" class="btn-marcar-lida text-[10px] text-white cursor-pointer bg-emerald-600 px-3 py-1 rounded-lg transition-colors whitespace-nowrap" data-id="{{ $notif->id }}">
                                    <i class="fas fa-check text-emerald-200 mr-1"></i> Marcar como lida
                                </button>
                            </div>
                        @endif
                    @elseif(!empty($notif->comentario_colaborador))
                        <div class="mt-2 p-2 bg-slate-900/50 rounded text-[10px] text-gray-400 border border-slate-700/50">
                            <span class="font-bold text-gray-500">Sua resposta:</span> {{ $notif->comentario_colaborador }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-sm text-gray-500">
                    <i class="fas fa-bell-slash text-2xl text-gray-600 mb-2 block"></i>
                    Sem notificações no momento.
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- JavaScript Nativo (Vanilla JS) para o Sino --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('btn-notificacoes-bell');
    const dropdown = document.getElementById('notificacoes-dropdown');
    const container = document.getElementById('notificacoes-bell-container');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (!bellBtn || !dropdown) return;

    // Toggle do Modal
    bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
    });

    // Fechar Modal ao clicar fora
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Impedir que cliques dentro do modal fechem o modal
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Função auxiliar para subtrair o contador visualmente
    function decrementBadge() {
        const badge = document.getElementById('notificacoes-badge');
        const countText = document.getElementById('notificacoes-count-text');
        
        if (countText) {
            let currentStr = countText.textContent;
            let currentNum = parseInt(currentStr);
            if (!isNaN(currentNum) && currentNum > 0) {
                let newNum = currentNum - 1;
                countText.textContent = newNum + ' não lidas';
                if (newNum === 0) {
                    if (badge) badge.remove();
                    countText.remove();
                }
            }
        } else if (badge) {
            badge.remove();
        }
    }

    // Lógica: Responder Notificação
    const btnResponderList = document.querySelectorAll('.btn-responder');
    btnResponderList.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const parentDiv = this.closest('.notificacao-item');
            const input = parentDiv.querySelector('.resposta-input');
            const formContainer = parentDiv.querySelector('.responder-form');
            const loading = parentDiv.querySelector('.resposta-loading');
            
            const resposta = input.value.trim();
            
            if (!resposta) {
                input.classList.add('border-red-500');
                setTimeout(() => input.classList.remove('border-red-500'), 2000);
                return;
            }

            // UI Loading state
            formContainer.classList.add('hidden');
            loading.classList.remove('hidden');

            // Fetch API (AJAX)
            fetch(`/notificacoes/${id}/responder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ resposta: resposta })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    parentDiv.classList.add('opacity-60');
                    loading.classList.add('hidden');
                    
                    // Mostra a resposta inline
                    const divResposta = document.createElement('div');
                    divResposta.className = 'mt-2 p-2 bg-slate-900/50 rounded text-[10px] text-gray-400 border border-slate-700/50';
                    divResposta.innerHTML = `<span class="font-bold text-gray-500">Sua resposta:</span> ${resposta}`;
                    parentDiv.appendChild(divResposta);
                    
                    decrementBadge();
                } else {
                    alert(data.message || 'Erro ao enviar resposta.');
                    formContainer.classList.remove('hidden');
                    loading.classList.add('hidden');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de rede ao enviar resposta.');
                formContainer.classList.remove('hidden');
                loading.classList.add('hidden');
            });
        });
    });

    // Lógica: Marcar como Lida
    const btnLidaList = document.querySelectorAll('.btn-marcar-lida');
    btnLidaList.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const parentDiv = this.closest('.notificacao-item');
            const thisBtnContainer = this.parentElement;
            
            // UI Loading state
            this.innerHTML = '<i class="fas fa-circle-notch fa-spin text-gray-400"></i>';

            // Fetch API (AJAX)
            fetch(`/notificacoes/${id}/marcar-lida`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    parentDiv.classList.add('opacity-60');
                    thisBtnContainer.remove();
                    decrementBadge();
                } else {
                    alert(data.message || 'Erro ao atualizar.');
                    this.innerHTML = '<i class="fas fa-check text-emerald-500 mr-1"></i> Marcar como lida';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de rede.');
                this.innerHTML = '<i class="fas fa-check text-emerald-500 mr-1"></i> Marcar como lida';
            });
        });
    });
});
</script>
