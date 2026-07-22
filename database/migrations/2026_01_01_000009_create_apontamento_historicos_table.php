<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: ApontamentoHistorico
 * Armazena o estado anterior (snapshot JSON) antes de uma edição.
 * Permite que o Gestor compare a versão original com a editada (Diff visual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apontamento_historicos', function (Blueprint $table) {
            $table->id();

            // Referência ao apontamento original — CASCADE (se o apontamento for excluído, o histórico também é)
            $table->foreignId('apontamento_original_id')
                ->constrained('apontamentos')->cascadeOnDelete()
                ->comment('Apontamento Original');

            // Snapshot JSON dos dados antes da edição
            $table->json('dados_snapshot')->comment('Cópia dos Dados (Snapshot)');

            // Quem fez a edição
            $table->foreignId('editado_por_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Editado Por');

            $table->timestamp('data_edicao')->useCurrent()->comment('auto_now_add equivalente');
            $table->integer('numero_edicao')->comment('Versão da Edição');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apontamento_historicos');
    }
};
