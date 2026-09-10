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
        Schema::create('projetos_operacionais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_operacional_id')->constrained('clientes_operacionais')->cascadeOnDelete();
            $table->string('codigo');
            $table->string('unidade');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['cliente_operacional_id', 'codigo', 'unidade'], 'proj_op_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projetos_operacionais');
    }
};
