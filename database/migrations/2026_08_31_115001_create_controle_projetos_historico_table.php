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
        Schema::create('controle_projetos_historico', function (Blueprint $table) {
            $table->id(); // SERIAL PRIMARY KEY
            
            // foreignId -> integer + fk (referencia erp_obras_manual) ON DELETE CASCADE
            $table->foreignId('projeto_original_id')
                  ->constrained('erp_obras_manual')
                  ->onDelete('cascade');
                  
            $table->jsonb('dados_snapshot')->nullable();
            
            // Apenas definindo o campo conforme solicitado. O relacionamento dependerá de como 'users' é gerido.
            $table->unsignedBigInteger('editado_por_id')->nullable();
            
            // data_edicao TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            $table->timestampTz('data_edicao')->useCurrent();
            
            $table->integer('numero_edicao')->nullable();
            
            // timestamps default do Laravel opcionais, mas mantendo o SQL estrito.
            // Se precisar do created_at / updated_at:
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_projetos_historico');
    }
};
