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
        Schema::table('produtividade_projeto', function (Blueprint $table) {
            $table->string('unidade', 100)->nullable();
            $table->dropColumn('nome');
            $table->dropUnique('produtividade_projeto_codigo_unique');
            $table->unique(['codigo_cliente_id', 'codigo', 'unidade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_projeto', function (Blueprint $table) {
            $table->dropUnique(['codigo_cliente_id', 'codigo', 'unidade']);
            $table->unique('codigo', 'produtividade_projeto_codigo_unique');
            $table->string('nome', 255)->nullable();
            $table->dropColumn('unidade');
        });
    }
};
