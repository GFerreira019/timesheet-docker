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
        Schema::table('apontamentos', function (Blueprint $table) {
            // Remove as FKs antigas
            $table->dropForeign(['codigo_cliente_id']);
            $table->dropForeign(['projeto_id']);
            
            // Adiciona as novas apontando para as tabelas operacionais
            $table->foreign('codigo_cliente_id')->references('id')->on('clientes_operacionais')->nullOnDelete();
            $table->foreign('projeto_id')->references('id')->on('projetos_operacionais')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apontamentos', function (Blueprint $table) {
            // Remove as novas FKs
            $table->dropForeign(['codigo_cliente_id']);
            $table->dropForeign(['projeto_id']);
            
            // Restaura as FKs antigas apontando para as tabelas originais
            $table->foreign('codigo_cliente_id')->references('id')->on('produtividade_codigocliente')->nullOnDelete();
            $table->foreign('projeto_id')->references('id')->on('produtividade_projeto')->nullOnDelete();
        });
    }
};
