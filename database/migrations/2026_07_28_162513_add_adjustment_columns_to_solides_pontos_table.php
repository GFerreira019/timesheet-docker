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
        Schema::table('solides_pontos', function (Blueprint $table) {
            $table->boolean('is_ajustado')->default(false);
            $table->text('justificativa')->nullable();
            $table->decimal('horas_abonadas', 8, 2)->nullable();
            $table->boolean('dia_trabalhado')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solides_pontos', function (Blueprint $table) {
            $table->dropColumn(['is_ajustado', 'justificativa', 'horas_abonadas', 'dia_trabalhado']);
        });
    }
};
