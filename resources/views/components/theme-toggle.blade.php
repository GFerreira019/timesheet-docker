<button id="themeToggleBtn" type="button" class="relative inline-flex items-center justify-center w-14 h-8 rounded-full transition-colors duration-300 focus:outline-none bg-slate-200 shadow-inner" title="Alternar Tema">
    <span class="sr-only">Alternar Tema</span>
    
    <!-- Slider (Bolinha Azul) -->
    <div id="themeToggleSlider" class="absolute top-1 left-1 w-6 h-6 rounded-full transition-transform duration-300 transform bg-blue-500 flex items-center justify-center translate-x-0">
        <!-- Ícone Sol (Modo Claro) -->
        <span id="theme-wrapper-sun" class="flex items-center justify-center">
            <i class="fas fa-sun text-white text-xs"></i>
        </span>
        <!-- Ícone Lua (Modo Escuro) -->
        <span id="theme-wrapper-moon" class="hidden items-center justify-center">
            <i class="fas fa-moon text-white text-xs"></i>
        </span>
    </div>
</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeToggleSlider = document.getElementById('themeToggleSlider');
        const htmlTag = document.documentElement;

        function updateUI(isDark) {
            // Agora manipulamos os spans, que não são destruídos pelo FontAwesome
            const sunWrapper = document.getElementById('theme-wrapper-sun');
            const moonWrapper = document.getElementById('theme-wrapper-moon');

            if (isDark) {
                themeToggleBtn.classList.remove('bg-slate-200');
                themeToggleBtn.classList.add('bg-slate-700');
                themeToggleSlider.classList.add('translate-x-6');
                
                if (sunWrapper) {
                    sunWrapper.classList.remove('flex');
                    sunWrapper.classList.add('hidden');
                }
                if (moonWrapper) {
                    moonWrapper.classList.remove('hidden');
                    moonWrapper.classList.add('flex');
                }
            } else {
                themeToggleBtn.classList.remove('bg-slate-700');
                themeToggleBtn.classList.add('bg-slate-200');
                themeToggleSlider.classList.remove('translate-x-6');
                
                if (sunWrapper) {
                    sunWrapper.classList.remove('hidden');
                    sunWrapper.classList.add('flex');
                }
                if (moonWrapper) {
                    moonWrapper.classList.remove('flex');
                    moonWrapper.classList.add('hidden');
                }
            }
        }

        // Verifica a preferência salva no localStorage ou no sistema
        const isDarkMode = localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDarkMode) {
            htmlTag.classList.add('dark');
        } else {
            htmlTag.classList.remove('dark');
        }

        // Define a UI inicial baseada no tema
        updateUI(isDarkMode);

        // Alterna o tema ao clicar
        themeToggleBtn.addEventListener('click', () => {
            htmlTag.classList.toggle('dark');
            const isDarkNow = htmlTag.classList.contains('dark');
            
            updateUI(isDarkNow);
            
            if (isDarkNow) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    });
</script>
