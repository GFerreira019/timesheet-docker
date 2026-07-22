@extends('layouts.app')
@section('title', 'Saúde do Sistema & Integrações')

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

<form id="form-config" action="{{ route('configuracoes.salvar') }}" method="POST" class="hidden">
    @csrf
</form>

{{-- ============================================================
            CABEÇALHO
            ============================================================ --}}
<x-page-header 
    title="Saúde do Sistema & Integrações" 
    subtitle="Monitorização de serviços e gestão de chaves de API"
    icon="fas fa-server"
    iconBg="from-slate-600 to-slate-800"
    iconColor="text-cyan-400"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3 max-w-full xl:max-w-7xl mx-auto px-4 sm:px-6 -mb-4 mt-2">       
    <button type="button" id="btn-testar-todos" class="bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition w-full sm:w-auto shadow-lg shadow-purple-900/20 text-sm whitespace-nowrap">
        <i class="fas fa-sync-alt"></i>
        <span class="inline">Testar Conexões</span>
    </button>
</div>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6 overflow-x-hidden">
    {{-- ============================================================
         CONTEÚDO PRINCIPAL (Grid Responsivo)
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

        {{-- COLUNA ESQUERDA (Status / Health Check) --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg">
                <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-700/50 pb-3">
                    <i class="fas fa-heartbeat text-pink-500"></i>
                    Status dos Serviços
                </h2>
                
                <ul class="space-y-4">
                    {{-- Banco de Dados --}}
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if($dbStatus)
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                            @else
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]"></div>
                            @endif
                            <span class="text-sm font-medium text-slate-200">Banco de Dados</span>
                        </div>
                        @if($dbStatus)
                        <span class="text-xs text-green-500">Online</span>
                        @else
                        <span class="text-xs text-red-500">Falha</span>
                        @endif
                    </li>
                    
                    {{-- Storage --}}
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if($storageStatus)
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                            @else
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]"></div>
                            @endif
                            <span class="text-sm font-medium text-slate-200">Storage</span>
                        </div>
                        @if($storageStatus)
                        <span class="text-xs text-green-500">Online</span>
                        @else
                        <span class="text-xs text-red-500">Falha</span>
                        @endif
                    </li>

                    {{-- Sólides API --}}
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div id="status-solides-dot" class="w-2.5 h-2.5 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(100,116,139,0.6)]"></div>
                            <span class="text-sm font-medium text-slate-200">Sólides API</span>
                            <a href="{{ route('pontos.index') }}" class="ml-1 text-[10px] text-blue-400 hover:text-blue-300 underline" title="Ir para Sólides">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                        <span id="status-solides-text" class="text-xs text-slate-400 font-bold bg-slate-700/50 px-2 py-0.5 rounded-md">Aguardando Teste</span>
                    </li>

                    {{-- Feriados API --}}
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div id="status-feriados-dot" class="w-2.5 h-2.5 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(100,116,139,0.6)]"></div>
                            <span class="text-sm font-medium text-slate-200">Feriados API</span>
                            <a href="{{ route('feriados.index') }}" class="ml-1 text-[10px] text-blue-400 hover:text-blue-300 underline" title="Ir para Feriados">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                        <span id="status-feriados-text" class="text-xs text-slate-400 font-bold bg-slate-700/50 px-2 py-0.5 rounded-md">Aguardando Teste</span>
                    </li>

                    {{-- WhatsApp Node.js --}}
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if($whatsappHealth['status'] === 'online')
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                            @else
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]"></div>
                            @endif
                            <span class="text-sm font-medium text-slate-200">{{ $whatsappHealth['name'] }}</span>
                            <a href="{{ route('whatsapp.index') }}" class="ml-1 text-[10px] text-blue-400 hover:text-blue-300 underline" title="Ir para Automação">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                        @if($whatsappHealth['status'] === 'online')
                        <span class="text-xs text-green-500" title="{{ $whatsappHealth['message'] }}">Online</span>
                        @else
                        <span class="text-xs text-red-500" title="{{ $whatsappHealth['message'] }}">Falha</span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        {{-- COLUNA DIREITA (Formulários de Integração) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Bloco 1: Integração Sólides --}}
            <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2 border-b border-slate-700/50 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-users-cog text-blue-400 text-lg"></i>
                        Integração Sólides
                    </h3>
                    <button type="button" id="btn-testar-solides" class="w-full sm:w-auto px-4 py-1.5 text-xs font-bold border border-blue-500/50 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 rounded-lg transition whitespace-nowrap flex items-center justify-center gap-2 shadow-sm">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </div>
                <div class="text-sm text-slate-400 mt-2">
                    <p>Valida o acesso ao endpoint da <a href="https://developer.api.solides.jobs/" target="_blank" class="text-blue-400 hover:underline">api.solides.com.br</a> utilizando a chave de ambiente.</p>
                    <div id="resultado-solides" class="mt-3 p-3 rounded-lg border border-slate-700/50 bg-slate-900/50 hidden text-xs font-medium transition"></div>
                </div>
            </div>

            {{-- Bloco Feriados API --}}
            <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2 border-b border-slate-700/50 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-calendar-check text-orange-400 text-lg"></i>
                        Integração Feriados API
                    </h3>
                    <button type="button" id="btn-testar-feriados" class="w-full sm:w-auto px-4 py-1.5 text-xs font-bold border border-orange-500/50 bg-orange-500/10 hover:bg-orange-500/20 text-orange-400 rounded-lg transition whitespace-nowrap flex items-center justify-center gap-2 shadow-sm">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </div>
                <div class="text-sm text-slate-400 mt-2">
                    <p>Valida o acesso ao endpoint da <a href="https://feriadosapi.com" target="_blank" class="text-orange-400 hover:underline">feriadosapi.com</a> utilizando a chave de ambiente.</p>
                    <div id="resultado-feriados" class="mt-3 p-3 rounded-lg border border-slate-700/50 bg-slate-900/50 hidden text-xs font-medium transition"></div>
                </div>
            </div>

            {{-- Bloco 2: Módulo WhatsApp --}}
            <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2 border-b border-slate-700/50 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fab fa-whatsapp text-green-500 text-lg"></i>
                        Integração WhatsApp (Node.js)
                    </h3>
                    <button type="button" id="btn-testar-whatsapp" class="w-full sm:w-auto px-4 py-1.5 text-xs font-bold border border-green-500/50 bg-green-500/10 hover:bg-green-500/20 text-green-400 rounded-lg transition whitespace-nowrap flex items-center justify-center gap-2 shadow-sm">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </div>
                <div class="text-sm text-slate-400 mt-2">
                    <p>Valida a comunicação com o servidor Node.js local e o status da sessão do WPPConnect.</p>
                    <div id="resultado-whatsapp" class="mt-3 p-3 rounded-lg border border-slate-700/50 bg-slate-900/50 hidden text-xs font-medium transition"></div>
                </div>
            </div>


        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sólides API
    const btnSolides = document.getElementById('btn-testar-solides');
    const resSolides = document.getElementById('resultado-solides');
    const dotSolides = document.getElementById('status-solides-dot');
    const textSolides = document.getElementById('status-solides-text');
    
    if (btnSolides) {
        btnSolides.addEventListener('click', function() {
            resSolides.classList.remove('hidden');
            resSolides.classList.remove('bg-green-500/10', 'text-green-400', 'bg-red-500/10', 'text-red-400');
            resSolides.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Testando conexão...';
            
            dotSolides.className = 'w-2.5 h-2.5 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(100,116,139,0.6)] animate-pulse';
            textSolides.className = 'text-xs text-slate-400 font-bold bg-slate-700/50 px-2 py-0.5 rounded-md';
            textSolides.innerText = 'Testando...';

            fetch('{{ route("configuracoes.testar_solides_api") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resSolides.classList.add('bg-green-500/10', 'text-green-400');
                    resSolides.innerHTML = '<i class="fas fa-check-circle mr-1"></i> ' + data.message;
                    
                    dotSolides.className = 'w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                    textSolides.className = 'text-xs text-green-500 font-bold bg-green-500/10 border border-green-500/20 px-2 py-0.5 rounded-md';
                    textSolides.innerText = 'Online';
                } else {
                    resSolides.classList.add('bg-red-500/10', 'text-red-400');
                    resSolides.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> ' + data.message;
                    
                    dotSolides.className = 'w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]';
                    textSolides.className = 'text-xs text-red-500 font-bold bg-red-500/10 border border-red-500/20 px-2 py-0.5 rounded-md';
                    textSolides.innerText = 'Falha';
                }
            })
            .catch(e => {
                resSolides.classList.add('bg-red-500/10', 'text-red-400');
                resSolides.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Erro interno de comunicação.';
                
                dotSolides.className = 'w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]';
                textSolides.className = 'text-xs text-red-500 font-bold bg-red-500/10 border border-red-500/20 px-2 py-0.5 rounded-md';
                textSolides.innerText = 'Falha';
            });
        });
    }

    // Feriados API
    const btnFeriados = document.getElementById('btn-testar-feriados');
    const resFeriados = document.getElementById('resultado-feriados');
    const dotFeriados = document.getElementById('status-feriados-dot');
    const textFeriados = document.getElementById('status-feriados-text');

    if (btnFeriados) {
        btnFeriados.addEventListener('click', function() {
            resFeriados.classList.remove('hidden');
            resFeriados.classList.remove('bg-green-500/10', 'text-green-400', 'bg-red-500/10', 'text-red-400');
            resFeriados.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Testando conexão...';
            
            // Reverter estado para aguardando
            dotFeriados.className = 'w-2.5 h-2.5 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(100,116,139,0.6)] animate-pulse';
            textFeriados.className = 'text-xs text-slate-400 font-bold bg-slate-700/50 px-2 py-0.5 rounded-md';
            textFeriados.innerText = 'Testando...';

            fetch('{{ route("configuracoes.testar_feriados_api") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resFeriados.classList.add('bg-green-500/10', 'text-green-400');
                    resFeriados.innerHTML = '<i class="fas fa-check-circle mr-1"></i> ' + data.message;
                    
                    dotFeriados.className = 'w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.6)]';
                    textFeriados.className = 'text-xs text-green-500 font-bold bg-green-500/10 border border-green-500/20 px-2 py-0.5 rounded-md';
                    textFeriados.innerText = 'Online';
                } else {
                    resFeriados.classList.add('bg-red-500/10', 'text-red-400');
                    resFeriados.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> ' + data.message;
                    
                    dotFeriados.className = 'w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]';
                    textFeriados.className = 'text-xs text-red-500 font-bold bg-red-500/10 border border-red-500/20 px-2 py-0.5 rounded-md';
                    textFeriados.innerText = 'Falha';
                }
            })
            .catch(e => {
                resFeriados.classList.add('bg-red-500/10', 'text-red-400');
                resFeriados.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Erro interno de comunicação.';
                
                dotFeriados.className = 'w-2.5 h-2.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]';
                textFeriados.className = 'text-xs text-red-500 font-bold bg-red-500/10 border border-red-500/20 px-2 py-0.5 rounded-md';
                textFeriados.innerText = 'Falha';
            });
        });
    }

    // WhatsApp API
    const btnWpp = document.getElementById('btn-testar-whatsapp');
    const resWpp = document.getElementById('resultado-whatsapp');
    
    if (btnWpp) {
        btnWpp.addEventListener('click', function() {
            resWpp.classList.remove('hidden');
            resWpp.classList.remove('bg-green-500/10', 'text-green-400', 'bg-red-500/10', 'text-red-400');
            resWpp.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Testando conexão...';
            
            fetch('{{ route("whatsapp.status") }}')
                .then(r => r.json())
                .then(data => {
                    if (data.status && data.status !== 'OFFLINE') {
                        resWpp.classList.add('bg-green-500/10', 'text-green-400');
                        resWpp.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Servidor online. Status da Sessão: ' + data.status;
                    } else {
                        resWpp.classList.add('bg-red-500/10', 'text-red-400');
                        resWpp.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Servidor Node.js está desligado ou inacessível.';
                    }
                })
                .catch(e => {
                    resWpp.classList.add('bg-red-500/10', 'text-red-400');
                    resWpp.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Erro interno de comunicação.';
                });
        });
    }

    // Botão de Teste Global
    const btnTestarTodos = document.getElementById('btn-testar-todos');
    if (btnTestarTodos) {
        btnTestarTodos.addEventListener('click', function() {
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
            this.disabled = true;
            this.classList.add('opacity-75', 'cursor-not-allowed');

            if (btnSolides) btnSolides.click();
            if (btnFeriados) btnFeriados.click();
            if (btnWpp) btnWpp.click();

            // Revert botão principal após 2 segundos (tempo suficiente para requests iniciarem)
            setTimeout(() => {
                this.innerHTML = originalHtml;
                this.disabled = false;
                this.classList.remove('opacity-75', 'cursor-not-allowed');
            }, 2000);
        });
    }
});
</script>
@endpush
@endsection
