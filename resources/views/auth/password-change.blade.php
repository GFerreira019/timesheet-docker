<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha — Sistema de Gestão de Obras</title>
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
        }
        .card {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 1.25rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        h1 { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin-bottom: 0.5rem; }
        p.subtitle { font-size: 0.8125rem; color: #64748b; margin-bottom: 1.75rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
        .form-group input { width: 100%; padding: 0.75rem 1rem; background: rgba(15,23,42,0.6); border: 1px solid rgba(99,102,241,0.25); border-radius: 0.625rem; color: #f1f5f9; font-size: 0.9rem; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; }
        .form-group input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .error-msg { font-size: 0.75rem; color: #f87171; margin-top: 0.3rem; display: block; }
        .btn { width: 100%; padding: 0.875rem; background: linear-gradient(135deg, #6366f1, #4f46e5); border: none; border-radius: 0.625rem; color: white; font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; }
        .btn:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); transform: translateY(-1px); }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.625rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #6ee7b7; margin-bottom: 1.25rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔐 Alterar Senha</h1>
        <p class="subtitle">Defina uma nova senha para sua conta.</p>

        @if (session('status') === 'password-updated')
            <div class="alert-success">✅ Senha alterada com sucesso!</div>
        @endif

        {{-- Equivalente ao template registration/password_change_form.html do Django --}}
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password">Senha Atual</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                @error('current_password', 'updatePassword')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Nova Senha</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password', 'updatePassword')
                    <span class="error-msg">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar Nova Senha</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn">Salvar Nova Senha</button>
        </form>
    </div>
</body>
</html>
