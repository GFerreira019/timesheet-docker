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
        Schema::create('apontamento_diario_obras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apontamento_id')->constrained('apontamentos')->onDelete('cascade');
            $table->text('texto_diario')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apontamento_diario_obras');
    }
};
