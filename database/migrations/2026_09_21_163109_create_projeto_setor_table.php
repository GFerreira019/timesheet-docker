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
        Schema::create('projeto_setor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erp_obra_manual_id')->constrained('erp_obras_manual')->onDelete('cascade');
            $table->foreignId('setor_id')->constrained('setores')->onDelete('cascade');
            $table->string('status')->nullable();
            $table->date('data_alteracao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('erp_obras_manual', function (Blueprint $table) {
            $table->dropForeign(['setor_id']);
            $table->dropColumn(['setor_id', 'projeto_etapa', 'projeto_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_obras_manual', function (Blueprint $table) {
            $table->foreignId('setor_id')->nullable()->constrained('setores');
            $table->string('projeto_etapa')->nullable();
            $table->string('projeto_status')->nullable();
        });

        Schema::dropIfExists('projeto_setor');
    }
};
