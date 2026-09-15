<div class="relative pl-6 border-l-2 border-slate-700/50 space-y-6">
    @forelse($timeline as $evento)
        <div class="relative">
            <!-- Marcador da Linha do Tempo -->
            <div class="absolute -left-[31px] top-1 h-4 w-4 rounded-full bg-blue-500 border-4 border-[#0B1120]"></div>
            
            <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700/50 shadow-lg mb-4">
                <!-- Cabeçalho da Edição -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-800 pb-3 mb-3">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-200">{{ $evento['autor'] }}</p>
                            <p class="text-xs text-slate-500"><i class="far fa-clock mr-1"></i> {{ $evento['data'] }} (Edição #{{ $evento['edicao'] }})</p>
                        </div>
                    </div>
                </div>

                <!-- Detalhes do De -> Para -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($evento['mudancas'] as $mudanca)
                        @php
                            // Se o texto for grande ou tiver quebras de linha, faremos o card ocupar as duas colunas
                            $deStr = $mudanca['de'] ?? '';
                            $paraStr = $mudanca['para'] ?? '';
                            $isLong = str_contains($deStr, '\n') || str_contains($paraStr, '\n') || strlen($deStr) > 40 || strlen($paraStr) > 40;
                        @endphp
                        <div class="bg-slate-800/40 rounded p-3 border border-slate-700/30 {{ $isLong ? 'md:col-span-2' : '' }}">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                {{ $mudanca['campo_formatado'] }}
                            </p>
                            @if(isset($mudanca['de']) && $mudanca['de'] === '' && $mudanca['para'] === 'Registro Inicial Criado')
                                <div class="text-sm text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded font-medium inline-block">
                                    <i class="fas fa-plus-circle mr-1"></i> Registro Inicial Criado
                                </div>
                            @else
                                <div class="flex items-center gap-3 text-sm flex-wrap">
                                    <span class="line-through text-red-400/80 bg-red-500/10 px-3 py-1.5 rounded break-words max-w-full">
                                        {!! nl2br(e(str_replace('\n', "\n", $mudanca['de'] ?: 'Vazio'))) !!}
                                    </span>
                                    <i class="fas fa-arrow-right text-slate-600 flex-shrink-0"></i>
                                    <span class="text-emerald-400 bg-emerald-500/10 px-3 py-1.5 rounded font-medium break-words max-w-full">
                                        {!! nl2br(e(str_replace('\n', "\n", $mudanca['para'] ?: 'Vazio'))) !!}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="text-slate-400 italic bg-slate-900/50 rounded-lg p-4 border border-slate-700/50">Nenhum histórico de alterações encontrado para este projeto.</div>
    @endforelse
</div>
