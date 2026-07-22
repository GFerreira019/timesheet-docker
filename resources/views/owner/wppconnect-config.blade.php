@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-whatsapp text-success me-2"></i> WPPConnect - Servidor de Mensagens
                        </h4>
                        <div>
                            @if($isOnline)
                                <span class="badge bg-success"><i class="bi bi-circle-fill small me-1"></i> Online</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-circle-fill small me-1"></i> Offline</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="card-body mt-3">
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle me-1"></i> Estabilidade do Servidor Node.js</strong><br>
                        Para garantir que as notificações cheguem sem interrupções, o servidor WPPConnect deve rodar nativamente via PM2. 
                        No terminal do servidor, execute:
                        <code class="d-block mt-2 bg-light p-2 rounded text-dark border">
                            cd /caminho/do/seu/wppconnect-server<br>
                            pm2 start src/server.ts --name "wpp-timesheet" --interpreter ./node_modules/.bin/ts-node<br>
                            pm2 save
                        </code>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('owner.wppconnect.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">API URL Base</label>
                            <input type="url" name="api_url" class="form-control" value="{{ old('api_url', $apiUrl) }}" placeholder="Ex: http://localhost:21465" required>
                            <div class="form-text">Endereço onde o WPPConnect Server está rodando (inclua a porta).</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Token de Segurança (Secret Key)</label>
                            <input type="password" name="api_token" class="form-control" value="{{ old('api_token', $apiToken) }}" required>
                            <div class="form-text">Token gerado ou configurado no WPPConnect para autorizar envios (Bearer).</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome da Sessão</label>
                            <input type="text" name="session_name" class="form-control" value="{{ old('session_name', $sessionName) }}" required>
                            <div class="form-text">Identificador da sessão criada no WPPConnect.</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary px-4">
                                Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
