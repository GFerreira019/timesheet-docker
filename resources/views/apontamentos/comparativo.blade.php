@extends('layouts.app')

@section('title', 'Comparativo Diário')

@push('head')
<style>
/* ==========================================================
   Header
   ========================================================== */
.header-gradient {
    background: linear-gradient(
        135deg,
        rgba(30,41,59,.95) 0%,
        rgba(15,23,42,.98) 100%
    );
}

/* ==========================================================
   Mobile Landscape Blocker (Overlay)
   ========================================================== */
.landscape-overlay {
    display: none; /* Hidden by default */
}

@media screen and (max-width: 768px) and (orientation: portrait) {
    /* Hide the main wrapper so it doesn't try to render weirdly */
    #landscape-wrapper {
        display: none !important;
    }
    
    /* Show the overlay */
    .landscape-overlay {
        display: flex;
        position: fixed;
        top: 0; 
        left: 0; 
        right: 0; 
        bottom: 0;
        background: #0d1321; /* Dark navy background */
        z-index: 99999;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
        padding: 2rem;
    }
    
    /* Animation for the phone icon */
    .rotate-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        color: #34d399; /* Emerald 400 */
        animation: tilt 2s infinite ease-in-out;
    }
    
    @keyframes tilt {
        0%, 100% { transform: rotate(0deg); }
        50% { transform: rotate(-90deg); }
    }
}
</style>
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<!-- Mobile Portrait Overlay Blocker -->
<div class="landscape-overlay">
    <i class="fas fa-mobile-alt rotate-icon"></i>
    <h3 class="text-2xl font-bold mb-3 text-white">Vire o seu celular</h3>
    <p class="text-slate-300 text-lg max-w-sm">
        Para uma melhor experiência visualizando a timeline do comparativo diário, por favor coloque o seu dispositivo na <b>horizontal</b>.
    </p>
</div>

<div id="landscape-wrapper">

<x-page-header 
    title="Comparativo Diário" 
    subtitle="Sólides x Timesheet ({{ \Carbon\Carbon::parse($data_comparativo)->format('d/m/Y') }} - {{ $colaborador->nome_completo ?? 'Colaborador não encontrado' }})"
    icon="fas fa-balance-scale"
    iconBg="from-emerald-500 to-emerald-700"
    backUrl="{{ $backUrl ?? url()->previous() }}">
</x-page-header>

<div class="max-w-full xl:max-w-7xl mx-auto p-4 sm:p-6">
    <div class="w-full overflow-x-auto bg-[#0d1321] p-4 md:p-8 rounded-xl my-2 shadow-2xl border border-slate-700">
        <div class="mb-8 md:mb-16 border-l-4 border-emerald-500 pl-4 sticky left-0">
            <h2 class="text-2xl font-bold text-white">Timeline do Dia</h2>
            <div class="flex gap-4 mt-2 text-sm">
                <span class="flex items-center gap-1 text-emerald-400"><i class="fas fa-circle text-[8px]"></i> Sólides</span>
                <span class="flex items-center gap-1 text-blue-400"><i class="fas fa-circle text-[8px]"></i> Timesheet</span>
            </div>
        </div>

        <div class="mt-8 relative min-w-[800px] py-24 flex items-center px-4">
            {{-- Linha e Seta --}}
            <div class="absolute left-0 right-10 top-1/2 h-1 bg-slate-300 -translate-y-1/2 z-0"></div>
            <div class="absolute right-6 top-1/2 -translate-y-1/2 w-0 h-0 border-t-[10px] border-t-transparent border-l-[16px] border-l-emerald-500 border-b-[10px] border-b-transparent z-10"></div>

            {{-- LOOP ENTRA AQUI --}}
            <div class="w-full flex justify-between relative z-10">
            @if(isset($timeline_data) && count($timeline_data) > 0)
                @foreach($timeline_data as $item)
                    @php
                        $has_ts = count($item['timesheet_data']) > 0;
                        $isEmbaixo = $loop->even;
                    @endphp
                    
                    <div class="relative z-10 flex items-center shrink-0 {{ !$has_ts ? 'w-32 justify-center' : 'mx-2' }}">
                        
                        {{-- BOLINHA INICIAL (Nó âncora) --}}
                        @if($item['is_solides'])
                            <div class="relative flex flex-col items-center z-10">
                                <div class="absolute bottom-full mb-4 flex flex-col items-center w-40">
                                    <span class="text-emerald-400 font-bold text-xl tracking-wider">{{ $item['hora'] }}</span>
                                    <span class="text-slate-200 text-sm font-medium mt-1">{{ $item['solides_data']['titulo'] }}</span>
                                    <span class="text-slate-400 text-xs text-center leading-tight mt-1">{{ $item['solides_data']['subtitulo'] }}</span>
                                    <div class="h-10 w-px border-l border-dashed border-slate-400 mt-3"></div>
                                </div>
                                <div class="w-4 h-4 bg-slate-300 rounded-full border-[3px] border-slate-950 shadow-[0_0_0_3px_#34d399] z-20 relative"></div>
                            </div>
                        @else
                            <div class="relative flex flex-col items-center z-30">
                                {{-- Bolinha com contraste (miolo cinza, borda escura, sombra azul) para não sumir na barra --}}
                                <div class="w-4 h-4 bg-slate-300 rounded-full border-[3px] border-slate-950 shadow-[0_0_0_3px_#3b82f6] z-20 relative"></div>
                                
                                <div class="absolute flex items-center {{ $isEmbaixo ? 'top-full mt-1 flex-col' : 'bottom-full mb-1 flex-col-reverse' }}">
                                    <div class="h-8 w-px border-l border-dashed border-[#3b82f6]"></div>
                                    <span class="text-[#3b82f6] font-bold text-xl tracking-wider {{ $isEmbaixo ? 'mt-1' : 'mb-1' }}">{{ $item['hora'] }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- BARRA TIMESHEET (Se houver) --}}
                        @if($has_ts)
                            @foreach($item['timesheet_data'] as $ts)
                                @php
                                    $is_connected_next = false;
                                    if (isset($timeline_data[$loop->parent->index + 1])) {
                                        $next = $timeline_data[$loop->parent->index + 1];
                                        if ($next['hora'] == $ts['hora_fim']) {
                                            $is_connected_next = true;
                                        }
                                    }
                                @endphp
                                
                                {{-- Linha Grossa Central --}}
                                <div class="relative h-5 bg-[#3b82f6] flex items-center justify-center min-w-[100px] px-3 -mx-3 z-0">
                                    <span class="relative z-10 text-white text-xs font-bold tracking-widest truncate pl-6">{{ $ts['codigo'] ?? 'S/ COD' }}</span>
                                    
                                    @if($is_connected_next)
                                        <div class="absolute top-0 left-[50%] w-[200px] h-full bg-[#3b82f6]"></div>
                                    @endif
                                </div>
                                
                                {{-- Bolinha Final (Só se NÃO conectar) --}}
                                @if(!$is_connected_next)
                                    <div class="relative flex flex-col items-center z-20">
                                        <div class="w-4 h-4 bg-[#3b82f6] rounded-full border-[3px] border-[#3b82f6] shadow-[0_0_0_2px_#3b82f6]"></div>
                                        <div class="absolute flex items-center {{ $isEmbaixo ? 'top-full mt-1 flex-col' : 'bottom-full mb-1 flex-col-reverse' }}">
                                            <div class="h-8 w-px border-l border-dashed border-[#3b82f6]"></div>
                                            <span class="text-[#3b82f6] font-bold text-xl tracking-wider {{ $isEmbaixo ? 'mt-1' : 'mb-1' }}">{{ $ts['hora_fim'] }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                        
                    </div>
                @endforeach
            @else
                <div class="w-full text-center text-slate-500 font-medium py-10 relative z-10 bg-[#0d1321] inline-block">
                    Nenhum registro de ponto encontrado para este dia.
                </div>
            @endif
            </div>
        </div>
    </div>
</div>
</div> <!-- End #landscape-wrapper -->
@endsection
