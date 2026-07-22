@props([
    'backUrl' => route('painel'),
    'backTitle' => 'Voltar',
    'icon' => 'fas fa-file',
    'iconColor' => 'text-cyan-400',
    'title' => 'Título',
    'subtitle' => null,
    'showThemeToggle' => true,
])

<header class="-mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-8 px-4 sm:px-6 lg:px-8 py-3 sm:py-4 mb-6 sm:mb-8 border-b border-slate-700/50 sticky top-0 z-50 backdrop-blur-lg bg-slate-900/80 flex items-center justify-between gap-2 sm:gap-4 flex-nowrap">
    {{-- Bloco Esquerdo (Navegação e Título) --}}
    <div class="flex items-center gap-4 min-w-0 flex-1">
        <a href="{{ $backUrl }}" class="p-2 rounded-lg hover:bg-slate-700/50 theme-text-muted hover:text-white transition shrink-0" title="{{ $backTitle }}">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="min-w-0">
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold break-words">
                @if($icon)
                <i class="{{ $icon }} mr-2 {{ $iconColor }}"></i>
                @endif
                {{ $title }}
            </h1>
            @if($subtitle)
            <p class="text-xs sm:text-sm theme-text-muted break-words">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    {{-- Bloco Direito (Ações) --}}
    <div class="flex items-center gap-4 shrink-0 justify-end">
        {{ $slot }}
    </div>

    {{-- Botão de Tema --}}
    @if ($showThemeToggle)
    <div class="flex items-center gap-4 shrink-0 justify-end">
        <x-theme-toggle />
    </div>
    @endif

    <div class="flex items-center gap-4 shrink-0 justify-end">
        <x-user-info />
    </div>
</header>
