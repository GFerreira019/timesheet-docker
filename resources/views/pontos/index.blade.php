@extends('layouts.app')
@section('title', 'Controle de Ponto (Sólides)')

@push('head')
<style>
    .header-gradient {
        background: linear-gradient(
            135deg,
            rgba(30,41,59,.95) 0%,
            rgba(15,23,42,.98) 100%
        );
    }
    .table-row-alt:nth-child(even) { background: rgba(15,23,42,0.3); }
    .table-row-alt:nth-child(odd) { background: transparent; }
    
    select option {
        background-color: #1e293b !important;
        color: #ffffff !important;
    }
</style>
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

{{-- CABEÇALHO --}}
<x-page-header 
    title="Espelho de Ponto" 
    subtitle="Integração em tempo real com API Sólides"
    icon="fas fa-clock"
    iconBg="from-indigo-500 to-indigo-700"
    backUrl="{{ route('painel') }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6">
    
    {{-- ERROS --}}
    @if($errors->any())
        <div class="mb-6 bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg shadow-sm">
            <ul class="list-disc list-inside text-sm font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FILTROS (Formulário GET) --}}
    <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-5 shadow-lg mb-8">
        <form action="{{ route('pontos.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
            
            <!-- SELECT COLABORADOR -->
            <div class="w-full sm:w-1/2">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Colaborador</label>
                <div class="relative">
                    @php
                        $opcoesColabs = [];
                        foreach($colaboradores as $colaborador) {
                            $opcoesColabs[$colaborador->id] = $colaborador->nome_completo;
                        }
                    @endphp
                    <x-select2 
                        id="select-colaborador" 
                        name="colaborador_id" 
                        placeholder="Selecione um colaborador..." 
                        :options="$opcoesColabs" 
                        :selected="$colaboradorId ?? request('colaborador_id', old('colaborador_id'))" 
                        required
                    />
                </div>
            </div>

            <!-- INPUT MÊS/ANO -->
            <div class="w-full sm:w-1/4">
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1.5">Mês/Ano</label>
                <input type="month" name="mes_ano" value="{{ $mesAno ?? date('Y-m') }}" required 
                    class="w-full h-10 bg-slate-900 border border-slate-600 rounded-lg px-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none uppercase">
            </div>

            <!-- BOTÕES -->
            <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                <button type="submit" class="w-full sm:w-auto h-10 px-5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-lg transition shadow-lg shadow-indigo-900/30 flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search"></i>
                    Buscar Ponto
                </button>
                
                <button type="button" id="btn-sync-todos" onclick="sincronizarTodos()" class="w-full sm:w-auto h-10 px-5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-lg transition shadow-lg shadow-blue-900/30 flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-sync-alt" id="icon-sync-todos"></i>
                    <span id="text-sync-todos">Sincronizar Todos</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ESPELHO DE PONTO (Grid Inferior) --}}
    @if(isset($pontos))
        @php
            $colabSelecionado = $colaboradores->firstWhere('id', $colaboradorId);
        @endphp
        <div class="bg-slate-800 border border-slate-700/50 rounded-xl shadow-lg overflow-hidden">
            <div class="p-5 border-b border-slate-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user fa-fw text-indigo-400"></i>
                        <span>{{ $colabSelecionado->nome_completo ?? 'Colaborador Desconhecido' }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 flex items-center gap-3">
                        <i class="fas fa-id-badge fa-fw text-slate-400"></i>
                        <span>{{ $colabSelecionado->cargo ?? '-' }}</span>
                    </p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center min-w-[900px]">
                    <thead class="bg-slate-900/50 text-[12px] uppercase tracking-wider text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Data</th>
                            <th class="px-3 py-3 font-semibold">Turno 1</th>
                            <th class="px-3 py-3 font-semibold">Turno 2</th>
                            <th class="px-3 py-3 font-semibold">Turno 3</th>
                            <th class="px-3 py-3 font-semibold">Turno 4</th>
                            <th class="px-3 py-3 font-semibold">Saldo do Dia</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs">
                        @forelse($pontos as $diaPonto)
                            <tr class="table-row-alt border-b border-slate-700/30 hover:bg-slate-700/20 transition group">
                                <td class="px-3 py-2 text-center">
                                    <p class="font-bold text-slate-200">
                                        {{ $diaPonto['data']->format('d/m/Y') }}
                                    </p>
                                    <p class="text-[9px] text-slate-500 uppercase">
                                        {{ $diaPonto['data']->translatedFormat('l') }}
                                    </p>
                                </td>
                                
                                @for($i = 0; $i < 4; $i++)
                                    <td class="px-3 py-2 text-indigo-300 font-bold border-l border-slate-700/30">
                                        @if(isset($diaPonto['turnos'][$i]))
                                            @php
                                                $turno = $diaPonto['turnos'][$i];
                                                $entrada = $turno->hora_entrada ? \Carbon\Carbon::parse($turno->hora_entrada)->format('H:i') : '--:--';
                                                $saida = $turno->hora_saida ? \Carbon\Carbon::parse($turno->hora_saida)->format('H:i') : '--:--';
                                                
                                                // Lógica do Ícone de Status
                                                $statusClass = 'text-slate-500';
                                                $statusIcon = 'fas fa-info-circle';
                                                
                                                if (in_array(strtoupper($turno->status), ['APPROVED', 'NORMAL', 'APROVADO'])) {
                                                    $statusClass = 'text-green-500';
                                                    $statusIcon = 'fas fa-check-circle';
                                                } elseif (in_array(strtoupper($turno->status), ['PENDING', 'AJUSTADO', 'PENDENTE'])) {
                                                    $statusClass = 'text-yellow-500';
                                                    $statusIcon = 'fas fa-exclamation-triangle';
                                                } elseif (empty($turno->hora_saida)) {
                                                    $statusClass = 'text-red-500';
                                                    $statusIcon = 'fas fa-exclamation-circle';
                                                }
                                            @endphp
                                            <div class="flex items-center justify-center gap-2">
                                                <span>{{ $entrada }} - {{ $saida }}</span>
                                                <i class="{{ $statusIcon }} {{ $statusClass }}" title="Status: {{ $turno->status }}"></i>
                                            </div>
                                        @else
                                            <span class="text-slate-500 font-normal">-</span>
                                        @endif
                                    </td>
                                @endfor
                                
                                <td class="px-3 py-2 text-emerald-400 font-bold text-sm border-l border-slate-700/30">
                                    {{ $diaPonto['saldo_dia'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-500">
                                        <i class="fas fa-folder-open text-4xl mb-3 text-slate-600"></i>
                                        <p class="font-medium text-sm text-slate-400">Nenhum registro encontrado para este período.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function sincronizarTodos() {
        const btn = document.getElementById('btn-sync-todos');
        const icon = document.getElementById('icon-sync-todos');
        const text = document.getElementById('text-sync-todos');
        const mesAno = document.querySelector('input[name="mes_ano"]').value;

        // Desabilita o botão e mostra o spinner
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        icon.classList.remove('fa-sync-alt');
        icon.classList.add('fa-spinner', 'fa-spin');
        text.innerText = "Sincronizando...";

        fetch('{{ route("pontos.sincronizar_todos") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mes_ano: mesAno })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro: ' + data.message);
                resetBtn();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao conectar com o servidor. A operação pode ter levado muito tempo (timeout) ou falhou.');
            resetBtn();
        });

        function resetBtn() {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            icon.classList.remove('fa-spinner', 'fa-spin');
            icon.classList.add('fa-sync-alt');
            text.innerText = "Sincronizar Todos";
        }
    }
</script>
@endpush
