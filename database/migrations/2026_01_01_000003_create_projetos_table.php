<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: Projeto
 * Cadastro centralizado de Obras e Projetos ativos da empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtividade_projeto', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique()->nullable()->comment('Código da Obra');
            $table->string('nome', 255)->comment('Nome do Projeto');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtividade_projeto');
    }
};
