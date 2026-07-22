<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Timesheet') | ATGB</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN (mesmo padrão do Django original) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#f0f9ff', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8' }
                    }
                }
            }
        }
    </script>

    <style>
        /* Micro-animações globais */
        * { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-slate-700/60 hover:text-white transition-all duration-200 text-sm font-medium; }
        .sidebar-link.active { @apply bg-indigo-600/20 text-indigo-400 border border-indigo-500/20; }
        .btn-primary { @apply inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-900/30 hover:shadow-indigo-900/50 hover:-translate-y-0.5; }
        .btn-danger  { @apply inline-flex items-center gap-2 px-5 py-2.5 bg-red-700/80 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-all duration-200; }
        .card        { @apply bg-slate-800/60 border border-slate-700/50 rounded-2xl shadow-xl backdrop-blur-sm; }
        .form-label  { @apply block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1.5; }
        .form-input  { @apply w-full bg-slate-900/80 border border-slate-600/60 text-white placeholder-gray-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all; }
        .badge       { @apply inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold; }

        /* Scrollbar fina */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>

    @stack('head')
</head>
<body class="h-full font-sans antialiased bg-slate-900 text-white min-h-screen">

{{-- ================================================================== --}}
{{-- WRAPPER PRINCIPAL: Sidebar + Conteúdo --}}
{{-- ================================================================== --}}
<div class="flex h-full">

    {{-- ============================================================== --}}
    {{-- CONTEÚDO PRINCIPAL FULLSCREEN --}}
    {{-- ============================================================== --}}
    <div class="flex-1 flex flex-col min-h-screen w-full">



        {{-- Flash messages --}}
        @if(session('success'))
        <div id="flash-success"
             class="mx-4 mt-4 flex items-start gap-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-xl text-sm font-medium animate-pulse-once">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-600 hover:text-emerald-400 transition-colors">✕</button>
        </div>
        @endif

        @if(session('error'))
        <div id="flash-error"
             class="mx-4 mt-4 flex items-start gap-3 px-4 py-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl text-sm font-medium">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto text-red-600 hover:text-red-400 transition-colors">✕</button>
        </div>
        @endif

        @if(session('warning'))
        <div class="mx-4 mt-4 flex items-start gap-3 px-4 py-3 bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-xl text-sm font-medium">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('warning') }}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto text-amber-600 hover:text-amber-400 transition-colors">✕</button>
        </div>
        @endif

        @if(session('info'))
        <div class="mx-4 mt-4 flex items-start gap-3 px-4 py-3 bg-blue-500/10 border border-blue-500/30 text-blue-400 rounded-xl text-sm font-medium">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('info') }}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto text-blue-600 hover:text-blue-400 transition-colors">✕</button>
        </div>
        @endif

        {{-- Conteúdo da página --}}
        <main class="w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-8 flex-1">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="py-4 px-6 text-center text-xs sm:text-sm text-gray-500 border-t border-slate-800">
            CONNECT v3.59.0 © 2026 
            <a href="https://atgbsistemas.com.br/" target="_blank" class="text-blue-400 hover:text-blue-300">ATGB SISTEMAS</a>
        </footer>
    </div>
</div>

{{-- Alpine.js para interatividade (dropdowns, modais) --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- Auto-dismiss flash messages --}}
<script>
    setTimeout(() => {
        document.getElementById('flash-success')?.remove();
        document.getElementById('flash-error')?.remove();
    }, 6000);
</script>

@stack('scripts')
@include('components.btn-suporte')
</body>
</html>
