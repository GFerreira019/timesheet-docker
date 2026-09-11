@extends('layouts.app')

@section('title', 'Treinamento')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')

<x-page-header 
    title="Treinamento" 
    subtitle="Aprenda a utilizar o sistema"
    icon="fas fa-graduation-cap text-rose-500"
    iconBg="from-rose-500 to-rose-700"
    backUrl="{{ route('timesheet.index') }}">
</x-page-header>

<div class="max-w-7xl mx-auto w-full px-0 sm:px-4 lg:px-6">

    {{-- ============================================================
         LAYOUT ESTRUTURAL COM ALPINE.JS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12" x-data="treinamentoApp()">
        
        {{-- ========================================================
             COLUNA DA ESQUERDA - PLAYER DE VÍDEO (lg:col-span-8)
             ======================================================== --}}
        <div class="lg:col-span-8">
            <div class="theme-bg-card border border-slate-700 rounded-xl p-4 sm:p-6">
                
                {{-- Container de Vídeo --}}
                <div class="aspect-video w-full rounded-lg overflow-hidden mb-6 bg-slate-900 border border-slate-700">
                    <iframe class="w-full h-full" :src="activeVideo.url" title="Vídeo de Treinamento" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>

                {{-- Informações do Vídeo --}}
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-white mb-2" x-text="activeVideo.titulo">Como utilizar o sistema</h2>
                        <p class="text-slate-400 text-sm sm:text-base" x-text="activeVideo.descricao">Aprenda o passo a passo para aproveitar todo o potencial da plataforma.</p>
                    </div>
                    <div class="bg-slate-800 text-slate-300 text-xs font-semibold px-3 py-1.5 rounded-lg flex items-center gap-2 border border-slate-700/50 shrink-0 self-start sm:self-auto">
                        <i class="far fa-clock text-rose-500"></i>
                        <span x-text="activeVideo.tempo">08:42</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================================================
             COLUNA DA DIREITA - LISTA DE CONTEÚDOS (lg:col-span-4)
             ======================================================== --}}
        <div class="lg:col-span-4">
            <div class="theme-bg-card border border-slate-700 rounded-xl p-4 sm:p-6">
                
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-list text-rose-500"></i>
                    Conteúdos do treinamento
                </h3>

                <div class="flex flex-col gap-3">
                    
                    {{-- Iteração com Alpine.js --}}
                    <template x-for="(video, index) in videos" :key="video.id">
                        <div 
                            @click="activeIndex = index"
                            :class="activeIndex === index 
                                ? 'border-rose-500/50 bg-rose-500/10 hover:bg-rose-500/20' 
                                : 'border-slate-700/50 hover:border-slate-600 hover:bg-slate-800/50 group'"
                            class="flex items-center gap-4 p-3 rounded-xl border cursor-pointer transition">
                            
                            {{-- Círculo com Número --}}
                            <div 
                                :class="activeIndex === index 
                                    ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/30' 
                                    : 'bg-slate-800 text-slate-300 group-hover:bg-slate-700'"
                                class="w-10 h-10 rounded-full flex items-center justify-center font-bold shrink-0 transition"
                                x-text="index + 1">
                            </div>
                            
                            {{-- Textos da Aula --}}
                            <div class="flex-1 overflow-hidden">
                                <h4 
                                    :class="activeIndex === index ? 'text-white' : 'text-slate-300 group-hover:text-white'"
                                    class="text-sm font-bold transition truncate" 
                                    x-text="video.titulo"></h4>
                                
                                <div 
                                    :class="activeIndex === index ? 'text-rose-400' : 'text-slate-500'"
                                    class="flex items-center gap-2 text-xs mt-1 transition">
                                    <i class="far fa-clock"></i>
                                    <span x-text="video.tempo"></span>
                                </div>
                            </div>
                            
                            {{-- Ícone Seta --}}
                            <i 
                                :class="activeIndex === index ? 'text-rose-500' : 'text-slate-600 group-hover:text-slate-400'"
                                class="fas fa-chevron-right text-sm transition"></i>
                        </div>
                    </template>

                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
    function treinamentoApp() {
        return {
            activeIndex: 0,
            videos: [
                {
                    id: 1,
                    titulo: 'Introdução ao Sistema',
                    descricao: 'Visão Geral do Sistema de apontamento de horas.',
                    tempo: '08:42',
                    url: 'https://www.youtube.com/embed/642xJ5Y2jJ8?si=DZ5Gi97DXVJ1lr8C?rel=0'
                },
                {
                    id: 2,
                    titulo: 'Apontamento de Horas',
                    descricao: 'Entenda como realizar apontamentos diários, visualizar histórico e acompanhar aprovações.',
                    tempo: '12:15',
                    url: 'https://www.youtube.com/embed/642xJ5Y2jJ8?si=DZ5Gi97DXVJ1lr8C?rel=0'
                },
                @hasanyrole('ADMIN|GERENCIAL|COORDENADOR')
                {
                    id: 3,
                    titulo: 'Aprovações de Apontamentos',
                    descricao: 'Como funcionam as aprovações de apontamentos.',
                    tempo: '05:30',
                    url: 'https://www.youtube.com/embed/642xJ5Y2jJ8?si=DZ5Gi97DXVJ1lr8C?rel=0'
                },
                @endhasanyrole
                @hasanyrole('ADMIN|GERENCIAL')
                {
                    id: 4,
                    titulo: 'Dashboard de Projetos',
                    descricao: 'Como interpretar os indicadores e métricas estratégicas da sua equipe.',
                    tempo: '09:45',
                    url: 'https://www.youtube.com/embed/642xJ5Y2jJ8?si=DZ5Gi97DXVJ1lr8C?rel=0'
                }
                @endhasanyrole
            ],
            get activeVideo() {
                return this.videos[this.activeIndex];
            }
        }
    }
</script>
@endpush
