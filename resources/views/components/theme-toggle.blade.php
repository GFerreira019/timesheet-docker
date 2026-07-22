<button id="themeToggleBtn" type="button" class="relative inline-flex items-center justify-center w-14 h-8 rounded-full transition-colors duration-300 focus:outline-none bg-slate-200 dark:bg-slate-700 shadow-inner" title="Alternar Tema">
    <span class="sr-only">Alternar Tema</span>
    
    <!-- Slider (Bolinha Azul) -->
    <div class="absolute top-1 left-1 w-6 h-6 rounded-full transition-transform duration-300 transform bg-blue-500 flex items-center justify-center translate-x-6 dark:translate-x-0">
        <!-- Ícone Sol (Modo Claro) -->
        <i class="fas fa-sun text-white text-xs dark:hidden block"></i>
        <!-- Ícone Lua (Modo Escuro) -->
        <i class="fas fa-moon text-white text-xs hidden dark:block"></i>
    </div>
</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const htmlTag = document.documentElement;

        // Verifica a preferência salva no localStorage ou no sistema
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlTag.classList.add('dark');
        } else {
            htmlTag.classList.remove('dark');
        }

        // Alterna o tema ao clicar
        themeToggleBtn.addEventListener('click', () => {
            htmlTag.classList.toggle('dark');
            if (htmlTag.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    });
</script>
