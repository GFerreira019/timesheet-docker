@props([
    'id' => 'calendarioModal',
    'titulo' => 'Verificar Registros',
    'dataRefStr' => \Carbon\Carbon::now()->format('Y-m-d'),
    'diasStatus' => [],
    'rotaFiltro' => null,
    'mostrarLegenda' => false
])

{{-- Componente Modal Calendário (Padrão CONNECT) --}}
<div id="{{ $id }}" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    {{-- Overlay com Blur --}}
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="document.getElementById('{{ $id }}').classList.add('hidden')"></div>
    
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            {{-- Container Principal do Modal --}}
            <div class="relative transform overflow-hidden rounded-2xl bg-slate-800 border border-slate-700 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md fade-in">
                
                {{-- Header --}}
                <div class="bg-slate-900/30 px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        {{ $titulo }}
                    </h3>
                    <button onclick="document.getElementById('{{ $id }}').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    {{-- Controles do Mês --}}
                    <div class="flex justify-between items-center text-white mb-6 bg-slate-900/50 p-2 rounded-xl border border-slate-700/50">
                        <button type="button" onclick="mudarMesCal_{{ $id }}(-1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-700 rounded-lg text-indigo-400 transition-colors">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="cal-label-{{ $id }}" class="font-bold text-sm uppercase tracking-widest text-white"></span>
                        <button type="button" onclick="mudarMesCal_{{ $id }}(1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-700 rounded-lg text-indigo-400 transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    {{-- Grid Calendário --}}
                    <div class="grid grid-cols-7 mb-2 text-center text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                        <div>D</div><div>S</div><div>T</div><div>Q</div><div>Q</div><div>S</div><div>S</div>
                    </div>
                    
                    <div id="cal-grid-{{ $id }}" class="grid grid-cols-7 gap-1.5">
                        <div class="col-span-7 text-center py-8 text-slate-500 italic">
                            <i class="fas fa-circle-notch fa-spin text-indigo-500 text-2xl"></i>
                        </div>
                    </div>

                    {{-- Legenda --}}
                    @if($mostrarLegenda)
                    <div class="mt-6 pt-4 border-t border-slate-700/50 flex gap-4 justify-center text-[10px] uppercase font-bold text-slate-400 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-green-500/20 border border-green-500/50"></div>
                            OK
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-yellow-500/20 border border-yellow-500/50"></div>
                            Incompleto
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-red-500/20 border border-red-500/50"></div>
                            Ausente
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-4 h-4 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center">
                                <i class="fas fa-paper-plane text-[8px] text-blue-400"></i>
                            </div>
                            Aviso enviado
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let dataAtualCal_{{ $id }} = new Date("{{ $dataRefStr }}T12:00:00");
    const labelMes_{{ $id }} = document.getElementById('cal-label-{{ $id }}');
    const gridCal_{{ $id }} = document.getElementById('cal-grid-{{ $id }}');

    const now_{{ $id }} = new Date();
    const hojeStr_{{ $id }} = new Date(now_{{ $id }}.getTime() - (now_{{ $id }}.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
    
    // Injetar status vindo do Laravel via props
    const diasStatusData_{{ $id }} = @json($diasStatus);

    // Função manual para abrir (caso usada diretamente)
    function abrirModalCalendario_{{ $id }}() {
        document.getElementById('{{ $id }}').classList.remove('hidden');
    }

    // Observer para detectar quando o modal for aberto por botões externos (ex: remove('hidden'))
    const observer_{{ $id }} = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
                const target = mutation.target;
                if (!target.classList.contains("hidden")) {
                    console.log(`[Calendario {{ $id }}] Modal aberto. Iniciando renderização...`);
                    // Se estiver no estado de loading, carrega. Ou recarrega.
                    carregarCalendario_{{ $id }}();
                }
            }
        });
    });
    
    // Inicia a observação assim que o DOM carregar (o elemento já existe no DOM)
    document.addEventListener("DOMContentLoaded", function() {
        const modalEl = document.getElementById('{{ $id }}');
        if (modalEl) {
            observer_{{ $id }}.observe(modalEl, { attributes: true });
        }
    });

    function mudarMesCal_{{ $id }}(delta) {
        dataAtualCal_{{ $id }}.setMonth(dataAtualCal_{{ $id }}.getMonth() + delta);
        carregarCalendario_{{ $id }}();
    }

    async function carregarCalendario_{{ $id }}() {
        const mes = dataAtualCal_{{ $id }}.getMonth() + 1;
        const ano = dataAtualCal_{{ $id }}.getFullYear();
        
        const nomeMes = dataAtualCal_{{ $id }}.toLocaleString('pt-BR', { month: 'long' });
        labelMes_{{ $id }}.textContent = `${nomeMes.charAt(0).toUpperCase() + nomeMes.slice(1)} ${ano}`;
        
        gridCal_{{ $id }}.innerHTML = `
            <div class="col-span-7 flex justify-center py-8">
                <i class="fas fa-circle-notch fa-spin text-indigo-500 text-2xl"></i>
            </div>`;

        try {
            console.log(`[Calendario {{ $id }}] Fetching /api/calendario/status?month=${mes}&year=${ano}`);
            const resp = await fetch(`/api/calendario/status?month=${mes}&year=${ano}`);
            
            if(!resp.ok) {
                throw new Error(`API retornou status ${resp.status}`);
            }

            const dados = await resp.json();
            console.log(`[Calendario {{ $id }}] Sucesso! Renderizando dias com base na API.`, dados);
            renderizarGrid_{{ $id }}(dados.days || [], ano, mes, dados.is_owner || false);
        } catch (e) {
            console.error(`[Calendario {{ $id }}] Falha na API, aplicando Renderização Local (Fallback). Erro:`, e);
            renderizarGrid_{{ $id }}([], ano, mes, true);
        }
    }

    function renderizarGrid_{{ $id }}(dias, ano, mes, isOwner) {
        gridCal_{{ $id }}.innerHTML = '';
        
        const primeiroDia = new Date(ano, mes - 1, 1).getDay();
        for (let i = 0; i < primeiroDia; i++) gridCal_{{ $id }}.appendChild(document.createElement('div'));

        // Fallback Seguro: Se o banco falhar e não trouxer dias, desenhamos o mês puro
        if (!dias || dias.length === 0) {
            const lastDay = new Date(ano, mes, 0).getDate();
            dias = []; // garante array limpo
            for(let i=1; i<=lastDay; i++) {
                const dStr = `${ano}-${String(mes).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
                dias.push({day: i, date: dStr, status: 'empty', is_owner: isOwner});
            }
        }

        dias.forEach(d => {
            // Mesclar status do backend (API) com o Status Mock/Props do Laravel
            const propStatus = diasStatusData_{{ $id }}[d.date] || {};
            const finalStatus = propStatus.status || d.status;
            
            const rotaFiltro = "{{ $rotaFiltro }}";
            
            // Base Class (adicionei relative para os ícones absolutos)
            let cl = "relative h-10 w-full flex items-center justify-center rounded-lg text-sm font-bold transition-all box-border ";
            
            const isSelected = (d.date === "{{ $dataRefStr }}");
            const isToday = (d.date === hojeStr_{{ $id }});

            // Lógica de Cores e Bordas
            if (isSelected) {
                cl += "bg-indigo-600 text-white border-2 border-indigo-400 z-20 shadow-lg shadow-indigo-900/30 cursor-pointer scale-105 ";
            } else if (finalStatus === 'ausente' || finalStatus === 'missing') {
                cl += 'bg-slate-700 text-red-400 border border-red-500/50 cursor-pointer hover:bg-red-500/30 hover:scale-105 ';
            } else if (finalStatus === 'incompleto' || finalStatus === 'incomplete') {
                cl += 'bg-slate-700 text-yellow-500 border border-yellow-500/50 cursor-pointer hover:bg-yellow-500/30 hover:scale-105 ';
            } else if (finalStatus === 'ok' || finalStatus === 'filled') {
                cl += 'bg-slate-700 text-green-400 border border-green-500/30 cursor-pointer hover:bg-green-500/20 hover:scale-105 ';
            } else if (finalStatus === 'day_off') {
                cl += 'bg-slate-800 text-slate-500 border border-slate-700 opacity-60 cursor-default ';
            } else if (finalStatus === 'future') {
                cl += 'bg-slate-900 text-slate-700 border border-slate-800 opacity-40 cursor-default pointer-events-none ';
            } else {
                cl += 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700 hover:text-white cursor-pointer ';
            }

            if (isToday && !isSelected) {
                cl += " ring-2 ring-indigo-500 ring-offset-2 ring-offset-slate-800 text-indigo-400"; 
            }
            
            // Cria elemento <a> se for rotaFiltro e o dia for válido, senão <div>
            const isClickable = (finalStatus !== 'future' && finalStatus !== 'day_off');
            const el = document.createElement( (rotaFiltro && isClickable) ? 'a' : 'div' );

            el.className = cl;
            el.textContent = d.day;

            // ==========================================
            // Ícones Flutuantes Absolutos (Badges) — Padrão Django
            // ==========================================

            // Badge canto ESQUERDO: Aviso/Notificação já enviada (avião de papel)
            if (d.ja_notificado || d.aviso_enviado || propStatus.aviso_enviado) {
                const badgeAviso = document.createElement('div');
                badgeAviso.title = 'Aviso já enviado neste dia';
                badgeAviso.className = 'absolute -top-1.5 -left-1.5 bg-slate-900 rounded-full border border-indigo-900 p-[1px] flex items-center justify-center w-[18px] h-[18px] shadow-md';
                badgeAviso.innerHTML = '<svg class="w-3 h-3 text-indigo-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>';
                el.appendChild(badgeAviso);
            }

            // Badge canto DIREITO: Status visual adicional (apenas visão gerencial/owner)
            if (isOwner) {
                if (finalStatus === 'filled') {
                    const icon = document.createElement('div');
                    icon.className = 'absolute -top-1.5 -right-1.5 bg-slate-900 rounded-full border border-emerald-900 flex items-center justify-center w-[18px] h-[18px]';
                    icon.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
                    el.appendChild(icon);
                }
            }

            if (isClickable) {
                if (rotaFiltro) {
                    el.href = rotaFiltro + '?data=' + d.date;
                } else {
                    el.onclick = () => { window.location.href = '?data=' + d.date; };
                }
            }

            gridCal_{{ $id }}.appendChild(el);
        });
        console.log(`[Calendario {{ $id }}] Renderização concluída (${dias.length} dias processados).`);
    }
</script>
@endpush
