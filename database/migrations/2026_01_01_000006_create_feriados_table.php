<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: Feriado
 * Tabela de feriados municipais, estaduais e nacionais.
 * Unique constraint em (data, cidade, uf) — mesmo do Django.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtividade_feriado', function (Blueprint $table) {
            $table->id();
            $table->date('data')->comment('Data do Feriado');
            $table->string('descricao', 100)->comment('Nome do Feriado');
            $table->string('cidade', 100);
            $table->string('uf', 2);
            $table->timestamps();

            // Equivalente ao unique_together = ('data', 'cidade', 'uf') do Django
            $table->unique(['data', 'cidade', 'uf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtividade_feriado');
    }
};
