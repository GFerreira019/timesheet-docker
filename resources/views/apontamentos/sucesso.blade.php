@extends('layouts.app')

@section('title', 'Registro Salvo')

@section('content')
<div class="flex items-center justify-center min-h-[70vh]">
    <div class="text-center max-w-md">
        <div class="w-20 h-20 rounded-full bg-emerald-500/20 border border-emerald-500/50 flex items-center justify-center mx-auto mb-6 animate-pulse">
            <svg class="w-10 h-10 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white mb-3">Registro Salvo!</h1>
        <p class="text-gray-400 mb-8">Seu apontamento foi registrado com sucesso no sistema.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('apontamentos.create') }}" class="btn-primary">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
                Novo Apontamento
            </a>
            <a href="{{ route('historico.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold rounded-xl transition-all">
                Ver Histórico
            </a>
        </div>
    </div>
</div>
@endsection
