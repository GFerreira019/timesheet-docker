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
        Schema::create('colaborador_cliente_gerenciado', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('colaborador_id');
            $table->unsignedBigInteger('codigo_cliente_id');
            $table->timestamps();

            $table->foreign('colaborador_id')->references('id')->on('produtividade_colaborador')->onDelete('cascade');
            $table->foreign('codigo_cliente_id')->references('id')->on('produtividade_codigocliente')->onDelete('cascade');
            $table->unique(['colaborador_id', 'codigo_cliente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_cliente_gerenciado');
    }
};
