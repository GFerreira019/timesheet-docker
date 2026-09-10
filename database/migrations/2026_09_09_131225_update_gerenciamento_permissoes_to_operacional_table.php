<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('colaborador_cliente_gerenciado', function (Blueprint $table) {
            // Remove as constraints e a coluna do banco legado
            $table->dropUnique(['colaborador_id', 'codigo_cliente_id']);
            $table->dropForeign(['codigo_cliente_id']);
            $table->dropColumn('codigo_cliente_id');

            // Adiciona a nova estrutura operacional
            $table->foreignId('cliente_operacional_id')
                  ->constrained('clientes_operacionais')
                  ->cascadeOnDelete();
                  
            $table->unique(['colaborador_id', 'cliente_operacional_id'], 'colab_cli_op_unique');
        });

        Schema::table('colaborador_projeto_gerenciado', function (Blueprint $table) {
            // Remove as constraints e a coluna do banco legado
            $table->dropUnique(['colaborador_id', 'projeto_id']);
            $table->dropForeign(['projeto_id']);
            $table->dropColumn('projeto_id');

            // Adiciona a nova estrutura operacional
            $table->foreignId('projeto_operacional_id')
                  ->constrained('projetos_operacionais')
                  ->cascadeOnDelete();
                  
            $table->unique(['colaborador_id', 'projeto_operacional_id'], 'colab_proj_op_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colaborador_cliente_gerenciado', function (Blueprint $table) {
            // Remove a nova estrutura
            $table->dropUnique('colab_cli_op_unique');
            $table->dropForeign(['cliente_operacional_id']);
            $table->dropColumn('cliente_operacional_id');

            // Recria a estrutura do banco legado
            $table->foreignId('codigo_cliente_id')
                  ->constrained('produtividade_codigocliente')
                  ->cascadeOnDelete();
                  
            $table->unique(['colaborador_id', 'codigo_cliente_id']);
        });

        Schema::table('colaborador_projeto_gerenciado', function (Blueprint $table) {
            // Remove a nova estrutura
            $table->dropUnique('colab_proj_op_unique');
            $table->dropForeign(['projeto_operacional_id']);
            $table->dropColumn('projeto_operacional_id');

            // Recria a estrutura do banco legado
            $table->foreignId('projeto_id')
                  ->constrained('produtividade_projeto')
                  ->cascadeOnDelete();
                  
            $table->unique(['colaborador_id', 'projeto_id']);
        });
    }
};
