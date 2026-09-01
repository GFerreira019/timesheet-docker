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
        Schema::create('erp_obras_manual', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_codigo', 255)->nullable();
            $table->string('projeto_codigo', 255)->nullable();
            $table->string('projeto_nome', 255)->nullable();
            $table->boolean('status_ativo')->default(true);
            $table->string('tipo_categoria', 50)->nullable();
            
            // Novos campos (Controle e Gerenciamento)
            $table->string('projeto_unidade', 100)->nullable();
            $table->string('projeto_objeto', 255)->nullable();
            $table->foreignId('setor_id')->nullable()->constrained('setores')->nullOnDelete();
            $table->string('projeto_etapa', 100)->nullable();
            $table->string('projeto_status', 50)->nullable();
            
            // Cronograma
            $table->boolean('ausencia_cronograma')->default(false);
            $table->date('cronograma_inicio')->nullable();
            $table->date('cronograma_fim')->nullable();
            
            $table->decimal('projeto_avanco', 5, 2)->nullable();
            
            // Gestores
            $table->foreignId('lider_comercial')->nullable()->constrained('produtividade_colaborador')->nullOnDelete();
            $table->foreignId('gerente_implantacao')->nullable()->constrained('produtividade_colaborador')->nullOnDelete();
            $table->foreignId('gerente_manutencao')->nullable()->constrained('produtividade_colaborador')->nullOnDelete();
            
            // Datas contratuais
            $table->date('target')->nullable();
            $table->date('contrato_assinatura')->nullable();
            $table->boolean('ausencia_contrato')->default(false);
            $table->date('termo_entrega')->nullable();
            $table->boolean('ausencia_termo')->default(false);
            
            // Dados Faturamento
            $table->string('cnpj', 18)->nullable();
            $table->string('razao_social', 255)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->boolean('pedagio')->nullable()->default(false);
            
            // Financeiro
            $table->decimal('valor_venda', 15, 2)->nullable();
            $table->decimal('valor_monitoramento', 15, 2)->nullable();
            $table->decimal('valor_licenca', 15, 2)->nullable();
            $table->decimal('valor_manutencao', 15, 2)->nullable();
            $table->decimal('valor_locacao', 15, 2)->nullable();
            
            $table->text('comentarios')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_obras_manual');
    }
};
