<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ErpObraManual;
use App\Models\ControleProjetoHistorico;

echo "--- Testando Histórico de Edições ---\n";

// Criar nova obra
$obra = ErpObraManual::create([
    'cliente_codigo' => 'CLI999',
    'projeto_codigo' => 'PROJ999',
    'projeto_nome' => 'Obra de Teste Histórico',
    'status_ativo' => true,
    'projeto_avanco' => 10.50,
    'comentarios' => 'Primeiro comentário'
]);

echo "Obra criada com ID: {$obra->id}\n";
$historico = ControleProjetoHistorico::where('projeto_original_id', $obra->id)->get();
echo "Históricos após CREATE: " . $historico->count() . "\n";
foreach ($historico as $h) {
    echo " Edição {$h->numero_edicao} - Avanço: " . ($h->dados_snapshot['projeto_avanco'] ?? 'N/A') . "\n";
}

// Atualizar a obra
$obra->projeto_avanco = 25.75;
$obra->comentarios = 'Segundo comentário - atualizado';
$obra->save();

echo "\nObra atualizada!\n";
$historico = ControleProjetoHistorico::where('projeto_original_id', $obra->id)->get();
echo "Históricos após UPDATE: " . $historico->count() . "\n";
foreach ($historico as $h) {
    echo " Edição {$h->numero_edicao} - Avanço: " . ($h->dados_snapshot['projeto_avanco'] ?? 'N/A') . " - Comentários: " . ($h->dados_snapshot['comentarios'] ?? 'N/A') . "\n";
}

// Limpeza
$obra->delete(); // O cascade vai apagar os históricos também
echo "\nTeste finalizado e registro apagado!\n";
