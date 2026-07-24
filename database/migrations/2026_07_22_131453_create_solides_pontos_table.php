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
        Schema::create('solides_pontos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solides_ponto_id')->unique();
            $table->foreignId('colaborador_id')->constrained('produtividade_colaborador')->onDelete('cascade');
            $table->date('data');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_saida')->nullable();
            $table->string('status', 50)->default('APPROVED');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solides_pontos');
    }
};
