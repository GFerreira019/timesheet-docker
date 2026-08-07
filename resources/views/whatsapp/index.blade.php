@extends('layouts.app')
@section('title', 'Automação WhatsApp')

@push('head')
<style>
    .header-gradient {
        background: linear-gradient(
            135deg,
            rgba(30,41,59,.95) 0%,
            rgba(15,23,42,.98) 100%
        );
    }
</style>
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

{{-- CABEÇALHO --}}
<x-page-header 
    title="Automação WhatsApp" 
    subtitle="Integração com WPPConnect para disparo de notificações"
    icon="fab fa-whatsapp text-green-500"
    iconBg="from-green-500 to-green-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6">
    
    {{-- MENSAGENS FLASH --}}
    @if(session('success'))
        <div class="mb-6 bg-green-500/10 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2 font-medium">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2 font-medium">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg shadow-sm">
            <ul class="list-disc list-inside text-sm font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- CARD 1: Status e Servidor Node --}}
        <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg flex flex-col">
            <div class="border-b border-slate-700/50 pb-3 mb-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-server text-green-400"></i>
                    Status do Servidor WPPConnect
                </h3>
            </div>

            <div class="flex-grow space-y-4">
                <div class="bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 px-4 py-3 rounded-lg text-sm flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle mt-0.5"></i>
                    <div>
                        <p class="font-bold mb-1">Atenção!</p>
                        <p>O botão abaixo força a inicialização do Node.js em background para **testes locais** no Windows. Em produção, utilize um gerenciador de processos como o PM2.</p>
                    </div>
                </div>

                <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700/50 flex flex-col items-center justify-center text-center gap-3">
                    <p class="text-sm text-slate-400">Status atual da Sessão:</p>
                    <span id="status-badge" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-700 text-slate-300">Verificando...</span>
                    
                    <div id="qr-container" class="hidden mt-2 flex flex-col items-center">
                        <p class="text-[10px] text-yellow-400 mb-2 uppercase tracking-widest font-bold">Escaneie o QR Code abaixo</p>
                        <img id="qr-code-img" class="rounded-xl border-4 border-slate-700 bg-white p-2 w-48 h-48 object-contain" src="" alt="QR Code">
                    </div>

                    <div class="mt-4 w-full border-t border-slate-700/50 pt-4 flex gap-3">
                        <form action="{{ route('whatsapp.iniciar_node') }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-lg transition border border-slate-600 hover:border-slate-500 shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-play text-green-400"></i>
                                Iniciar Node.js
                            </button>
                        </form>

                        <form action="{{ route('whatsapp.parar_node') }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" onclick="return confirm('Tem certeza que deseja forçar a parada do serviço Node?')" class="w-full px-4 py-2.5 bg-red-900/30 hover:bg-red-800/40 text-red-400 text-sm font-bold rounded-lg transition border border-red-500/30 hover:border-red-400/50 shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-power-off"></i>
                                Desligar
                            </button>
                        </form>

                        <form action="{{ route('whatsapp.atualizar_wppconnect') }}" method="POST" class="flex-none" onsubmit="this.querySelector('button').innerHTML = '<i class=\'fas fa-spinner fa-spin text-blue-400\'></i>'; this.querySelector('button').classList.add('opacity-50', 'cursor-not-allowed');">
                            @csrf
                            <button type="submit" title="Atualizar WPPConnect" class="h-full px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-lg transition border border-slate-600 hover:border-slate-500 shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-sync-alt text-blue-400"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: Teste de Envio --}}
        <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg flex flex-col">
            <div class="border-b border-slate-700/50 pb-3 mb-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-paper-plane text-blue-400"></i>
                    Disparo de Teste
                </h3>
            </div>

            <form action="{{ route('whatsapp.enviar_teste') }}" method="POST" class="flex-grow flex flex-col">
                @csrf
                <div class="space-y-4 flex-grow">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Número de Telefone</label>
                        <input type="text" name="telefone" placeholder="Ex: 11987654321" required
                               class="w-full bg-slate-900 border border-slate-600 rounded-lg p-2.5 text-white text-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition">
                        <p class="text-[10px] text-slate-500 mt-1">O código do país (+55) será inserido automaticamente pelo serviço.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Mensagem de Teste</label>
                        <textarea name="mensagem" rows="3" placeholder="Escreva a mensagem (suporta formatação do WhatsApp como *negrito*)" required
                                  class="w-full bg-slate-900 border border-slate-600 rounded-lg p-2.5 text-white text-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition resize-none"></textarea>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-700/50 flex flex-col items-end">
                    <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-500 text-white text-sm font-bold rounded-lg transition shadow-lg shadow-green-900/30 flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Teste via API
                    </button>
                    <p class="text-xs text-slate-400 mt-2">A API do Laravel enviará a requisição para a URL configurada no sistema (Ex: localhost:3000).</p>
                </div>
            </form>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrImg = document.getElementById('qr-code-img');
    const badge = document.getElementById('status-badge');
    const qrContainer = document.getElementById('qr-container');
    
    // Inicia o loop infinito de verificação
    setInterval(() => {
        fetch('{{ route("whatsapp.status") }}')
            .then(response => {
                if (!response.ok) throw new Error('Servidor Node não respondeu');
                return response.json();
            })
            .then(data => {
                const statusRaw = data.status_raw || data.status || '?';
                
                // QR Code disponível para leitura
                if (data.status === 'QR_CODE' && data.qrcode) {
                    if (qrImg.src !== data.qrcode) {
                        qrImg.src = data.qrcode;
                    }
                    qrContainer.classList.remove('hidden');
                    badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-yellow-500/20 text-yellow-500 border border-yellow-500/50 animate-pulse';
                    badge.innerText = 'Aguardando Leitura do QR...';
                }
                // ✅ CONECTADO: O Controller PHP é quem valida (data.conectado == true)
                else if (data.conectado === true) {
                    qrContainer.classList.add('hidden');
                    qrImg.src = '';
                    badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/50';
                    badge.innerText = '✅ Conectado (' + statusRaw + ')';
                }
                // Sincronizando após leitura do QR Code
                else if (statusRaw === 'qrReadSuccess') {
                    qrContainer.classList.add('hidden');
                    badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/50 animate-pulse';
                    badge.innerText = '🔄 Aguardando Sincronização...';
                }
                // ❌ DESCONECTADO: exibe o status_raw exato para facilitar debug
                else {
                    qrContainer.classList.add('hidden');
                    badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/50';
                    
                    const labels = {
                        'notLogged':          '❌ Não Logado',
                        'disconnectedMobile': '📵 Celular Desconectado',
                        'browserClose':       '🚫 Sessão Fechada (browserClose)',
                        'autocloseCalled':    '🚫 Sessão Fechada (autoclose)',
                        'OFFLINE':            '🔴 Serviço Offline',
                        'NODE_OFFLINE':       '🔴 Node.js Desligado',
                    };
                    badge.innerText = (labels[statusRaw] || ('⚠️ ' + statusRaw));
                }
            })
            .catch(error => {
                // Node.js está completamente desligado
                qrContainer.classList.add('hidden');
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/50';
                badge.innerText = 'Desligado / Offline';
            });
    }, 3000);
});
</script>
@endpush

@endsection
