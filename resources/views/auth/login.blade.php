<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistema de Gestão de Obras</title>
    <meta name="description" content="Acesse o sistema de controle de produtividade e timesheet de obras.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(16, 185, 129, 0.10) 0%, transparent 60%);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }

        .login-card {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5),
                        0 0 0 1px rgba(255,255,255,0.05);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo .icon-wrap {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #6366f1 0%, #10b981 100%);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
        }

        .login-logo .icon-wrap svg {
            width: 32px;
            height: 32px;
            color: white;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        .login-logo h1 {
            font-size: 1.375rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.02em;
        }

        .login-logo p {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 0.625rem;
            color: #f1f5f9;
            font-size: 0.9375rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            background: rgba(15, 23, 42, 0.8);
        }

        .form-group input::placeholder { color: #334155; }

        .form-group .error-msg {
            font-size: 0.75rem;
            color: #f87171;
            margin-top: 0.375rem;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .remember-row input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: #6366f1;
            cursor: pointer;
        }

        .remember-row label {
            font-size: 0.875rem;
            color: #64748b;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 0.625rem;
            color: white;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            letter-spacing: 0.01em;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-login:active { transform: translateY(0); }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #fca5a5;
            margin-bottom: 1.25rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #334155;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">

            {{-- Logo e Título --}}
            <div class="login-logo">
                <div class="icon-wrap">
                    {{-- Ícone de construção/obra --}}
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                    </svg>
                </div>
                <h1>Gestão de Obras</h1>
                <p>Sistema de Produtividade & Timesheet</p>
            </div>

            {{-- Erros gerais de autenticação --}}
            @if ($errors->any())
                <div class="alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Session Status (ex: senha alterada) --}}
            @if (session('status'))
                <div class="alert-error" style="background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.3); color: #6ee7b7;">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Formulário de Login --}}
            {{-- Equivalente ao template registration/login.html do Django --}}
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Usuário --}}
                <div class="form-group">
                    <label for="email">Usuário (E-mail)</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="seu@email.com"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Senha --}}
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Lembrar-me --}}
                <div class="remember-row">
                    <input type="checkbox" id="remember_me" name="remember">
                    <label for="remember_me">Manter sessão ativa</label>
                </div>

                {{-- Botão Entrar --}}
                <button type="submit" class="btn-login">
                    Entrar no Sistema
                </button>
            </form>

            <div class="footer-text">
                Sistema de Uso Interno &bull; Acesso Restrito
            </div>
        </div>
    </div>
</body>
</html>
