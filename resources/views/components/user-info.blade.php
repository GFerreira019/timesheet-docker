<div class="text-right hidden sm:block">
    <p class="text-sm font-medium text-white">
        {{ auth()->user()->name ?? 'Administrador' }}
    </p>
    <p class="text-left text-xs text-slate-400">
        {{ auth()->user()->email ?? 'admin@atgbsistemas.com.br' }}
    </p>
</div>
