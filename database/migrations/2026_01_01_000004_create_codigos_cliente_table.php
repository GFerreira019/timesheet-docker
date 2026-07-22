<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: CodigoCliente
 * Cadastro de Códigos Gerais de Cliente padronizados com 4 dígitos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtividade_codigocliente', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 4)->unique()->comment('Cód. Cliente (4 Dígitos)');
            $table->string('nome', 255)->comment('Nome do Cliente');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtividade_codigocliente');
    }
};
