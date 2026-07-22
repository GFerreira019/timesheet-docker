<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: Veiculo
 * Cadastro da frota oficial e veículos de apoio da empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtividade_veiculo', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->unique()->comment('Placa');
            $table->string('descricao', 100)->nullable()->comment('Modelo/Descrição');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtividade_veiculo');
    }
};
