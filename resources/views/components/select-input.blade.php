@props(['disabled' => false])

<div class="relative">
    <select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'w-full bg-slate-800 border border-slate-600 rounded-lg p-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none appearance-none cursor-pointer transition-all hover:bg-slate-700']) !!}>
        {{ $slot }}
    </select>
    
    <!-- Ícone de seta -->
    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
        <i class="fas fa-chevron-down"></i>
    </div>
</div>