@extends('layouts.app')

@section('title', 'Detalhes da Obra (ERP)')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@section('content')
<x-page-header 
    title="Detalhes do Projeto" 
    subtitle="{{ $obra->projeto_codigo ?? '-' }} - {{ $obra->projeto_nome }}"
    icon="fas fa-building text-blue-500"
    iconBg="from-blue-500 to-blue-700"
    backUrl="{{ route('erp-obras-manual.index') }}">
</x-page-header>

<div class="max-w-7xl mx-auto px-4 pb-6 pt-2 sm:px-6">
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 shadow-xl overflow-hidden">
        <!-- Cabeçalho do Card -->
        <div class="px-6 py-4 border-b border-slate-700/50 bg-slate-900/50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-200">Visão Geral da Obra</h3>
            @if($obra->status_ativo)
                <span class="px-3 py-1 bg-green-500/20 text-green-400 border border-green-500/30 rounded-lg text-xs font-bold uppercase tracking-wider">Ativo</span>
            @else
                <span class="px-3 py-1 bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg text-xs font-bold uppercase tracking-wider">Inativo</span>
            @endif
        </div>

        <!-- Corpo: Grid em Colunas -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Coluna 1: Dados Gerais -->
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2"><i class="fas fa-info-circle mr-2"></i> Dados Gerais</h4>
                        
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Código do Projeto</p>
                            <p class="text-sm text-slate-200 font-bold">{{ $obra->projeto_codigo ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Nome do Projeto</p>
                            <p class="text-sm text-slate-200">{{ $obra->projeto_nome ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Unidade</p>
                            <p class="text-sm text-slate-200">{{ $obra->projeto_unidade ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Objeto</p>
                            <p class="text-sm text-slate-200">{{ $obra->projeto_objeto ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Categoria</p>
                            <p class="text-sm text-slate-200">{{ $obra->tipo_categoria ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Setor / Etapa</p>
                            <p class="text-sm text-slate-200">{{ $obra->setor->nome ?? $obra->projeto_setor ?? '-' }} {{ $obra->projeto_etapa ? ' - ' . $obra->projeto_etapa : '' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Coluna 2: Cliente e Localização -->
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2"><i class="fas fa-building mr-2"></i> Cliente e Localização</h4>
                        
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Código do Cliente</p>
                            <p class="text-sm text-slate-200">{{ $obra->cliente_codigo ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Razão Social</p>
                            <p class="text-sm text-slate-200">{{ $obra->razao_social ?? $obra->cliente_razao_social ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">CNPJ</p>
                            <p class="text-sm text-slate-200">{{ $obra->cnpj ?? '-' }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Cidade</p>
                            <p class="text-sm text-slate-200">
                                {{ $obra->cidade ?? '-' }}
                                @if($obra->pedagio)
                                    <span class="ml-2 text-xs text-orange-400 bg-orange-500/10 px-2 py-0.5 rounded border border-orange-500/20"><i class="fas fa-road-barrier"></i> Pedágio</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Endereço</p>
                            <p class="text-sm text-slate-200">{{ $obra->endereco ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Coluna 3: Cronograma e Status -->
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2"><i class="fas fa-calendar-check mr-2"></i> Cronograma</h4>
                        
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Status do Projeto</p>
                            <p class="text-sm text-slate-200 font-bold">{{ $obra->projeto_status ?? '-' }}</p>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Avanço</p>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-slate-700 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $obra->projeto_avanco ?? 0 }}%"></div>
                                </div>
                                <span class="text-sm font-bold text-indigo-400">{{ $obra->projeto_avanco ?? 0 }}%</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="mb-4">
                                <p class="text-xs text-slate-500 font-semibold mb-1">Início</p>
                                <p class="text-sm text-slate-200">{{ $obra->cronograma_inicio ? \Carbon\Carbon::parse($obra->cronograma_inicio)->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div class="mb-4">
                                <p class="text-xs text-slate-500 font-semibold mb-1">Fim</p>
                                <p class="text-sm text-slate-200">{{ $obra->cronograma_fim ? \Carbon\Carbon::parse($obra->cronograma_fim)->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div class="mb-4">
                                <p class="text-xs text-slate-500 font-semibold mb-1">Assinatura</p>
                                <p class="text-sm text-slate-200">{{ $obra->contrato_assinatura ? \Carbon\Carbon::parse($obra->contrato_assinatura)->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div class="mb-4">
                                <p class="text-xs text-slate-500 font-semibold mb-1">Termo Entrega</p>
                                <p class="text-sm text-slate-200">{{ $obra->termo_entrega ? \Carbon\Carbon::parse($obra->termo_entrega)->format('d/m/Y') : '-' }}</p>
                            </div>
                            <div class="mb-4 col-span-2">
                                <p class="text-xs text-slate-500 font-semibold mb-1">Target</p>
                                <p class="text-sm text-slate-200">{{ $obra->target ? \Carbon\Carbon::parse($obra->target)->format('d/m/Y') : '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Divisor -->
            <div class="my-8 border-t border-slate-700/50"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Coluna: Gestores -->
                <div>
                    <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2"><i class="fas fa-users mr-2"></i> Gestores</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Responsável Comercial</p>
                            <p class="text-sm text-slate-200">{{ $obra->liderComercial->nome_completo ?? $obra->lider_comercial ?? '-' }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Coordenadores</p>
                            <div class="text-sm text-slate-200">
                                @forelse(($obra->projetoOperacional->gestores ?? []) as $coordenador)
                                    <span class="block">{{ $coordenador->nome_completo }}</span>
                                @empty
                                    -
                                @endforelse
                            </div>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Gerente de Implantação</p>
                            <p class="text-sm text-slate-200">{{ $obra->gerenteImplantacao->nome_completo ?? '-' }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Gerente de Manutenção</p>
                            <p class="text-sm text-slate-200">{{ $obra->gerenteManutencao->nome_completo ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Financeiro -->
                <div>
                    <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2"><i class="fas fa-wallet mr-2"></i> Financeiro</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Valor de Venda</p>
                            <p class="text-sm text-slate-200">R$ {{ number_format($obra->valor_venda, 2, ',', '.') }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Monitoramento</p>
                            <p class="text-sm text-slate-200">R$ {{ number_format($obra->valor_monitoramento, 2, ',', '.') }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Licença</p>
                            <p class="text-sm text-slate-200">R$ {{ number_format($obra->valor_licenca, 2, ',', '.') }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Manutenção</p>
                            <p class="text-sm text-slate-200">R$ {{ number_format($obra->valor_manutencao, 2, ',', '.') }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-xs text-slate-500 font-semibold mb-1">Locação</p>
                            <p class="text-sm text-slate-200">R$ {{ number_format($obra->valor_locacao, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($obra->comentarios)
            <!-- Divisor -->
            <div class="my-8 border-t border-slate-700/50"></div>
            <div>
                <h4 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2"><i class="fas fa-comment-dots mr-2"></i> Comentários</h4>
                <div class="bg-slate-900/50 p-4 rounded-lg border border-slate-700">
                    <p class="text-sm text-slate-300 whitespace-pre-wrap">{{ $obra->comentarios }}</p>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
