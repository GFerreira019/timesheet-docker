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
        Schema::create('colaborador_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('produtividade_colaborador')->onDelete('cascade');
            $table->foreignId('user_id_alteracao')->nullable()->constrained('users')->nullOnDelete();
            $table->json('dados_anteriores')->nullable();
            $table->json('campos_alterados')->nullable();
            $table->date('data_vigencia')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_historicos');
    }
};
