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
        Schema::create('colaborador_projeto_gerenciado', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('colaborador_id'); 
            $table->unsignedBigInteger('projeto_id'); 
            $table->timestamps();

            $table->foreign('colaborador_id')->references('id')->on('produtividade_colaborador')->onDelete('cascade');
            $table->foreign('projeto_id')->references('id')->on('produtividade_projeto')->onDelete('cascade');
            
            // Evitar duplicidade de vínculo
            $table->unique(['colaborador_id', 'projeto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_projeto_gerenciado');
    }
};
