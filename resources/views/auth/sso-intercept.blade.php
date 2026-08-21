@section('title', 'Acesso Restrito')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@push('head')
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - Timesheet | ATGB</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('storage/suporte/anexos/icon-192x192.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (seguindo o layout global do projeto) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        * { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
        .card { @apply bg-slate-800/60 border border-slate-700/50 rounded-2xl shadow-xl backdrop-blur-sm; }
        .btn-primary { @apply inline-flex items-center justify-center gap-2 px-5 py-3.5 w-full bg-cyan-600 hover:bg-cyan-500 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-cyan-900/30 hover:shadow-cyan-900/50 hover:-translate-y-0.5; }
    </style>
</head>
<body class="h-full font-sans antialiased text-white min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    
    <div class="card p-8 sm:p-10 max-w-md w-full text-center mx-4">
        <!-- Ícone de Cadeado/Segurança -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-2xl bg-cyan-500/10 mb-6 border border-cyan-500/20 shadow-inner">
            <svg class="h-10 w-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-white mb-3">Acesso Restrito</h2>
        
        <p class="text-slate-400 text-sm mb-8 leading-relaxed">
            Este é um ambiente corporativo fechado. O acesso ao módulo de Timesheet deve ser feito exclusivamente através do painel principal do sistema ERP.
        </p>

        <!-- Tratamento de Erros Vindo do SsoController -->
        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 mb-8 text-sm text-left rounded-xl shadow-sm">
                <div class="flex items-center mb-2">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span class="font-bold">Aviso de Segurança:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 ml-1 text-red-300">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a href="{{ config('services.erp.panel_url', 'https://atgbconnect.com.br/') }}" 
            class="group flex sm:inline-flex items-center justify-center gap-2 px-6 py-3 mb-6 mx-auto w-full sm:w-auto text-sm font-bold text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 rounded-lg shadow-inner hover:bg-cyan-500/20 hover:border-cyan-500/40 hover:text-cyan-300 transition-all duration-300">
            <span>Acessar ATGB Connect</span>
            <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
        </a>
    </div>

</body>
</html>
