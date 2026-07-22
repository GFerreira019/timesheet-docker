<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: Notificacao
 * Armazena alertas e mensagens do sistema para o colaborador.
 * Suporta resposta do colaborador (comentario_colaborador).
 *
 * TIPO_CHOICES: ALERTA | INFO | SUCESSO
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();

            // Destinatário — CASCADE: se o colaborador for excluído, suas notificações também
            $table->foreignId('colaborador_id')
                ->constrained('produtividade_colaborador')->cascadeOnDelete()
                ->comment('Destinatário');

            $table->string('titulo', 100)->comment('Título');
            $table->text('mensagem')->comment('Conteúdo da Mensagem');

            // TIPO_CHOICES equivalente ao Django
            $table->string('tipo', 10)->default('INFO')
                ->comment('ALERTA | INFO | SUCESSO');

            $table->boolean('lida')->default(false)->comment('Lida?');

            // data_criacao: auto_now_add no Django
            $table->timestamp('data_criacao')->useCurrent();

            // Data de referência para alertas de conformidade
            $table->date('data_referencia')->nullable()
                ->comment('Data de Referência (Para Alertas)');

            // Resposta do colaborador ao alerta (visível para o Owner)
            $table->text('comentario_colaborador')->nullable()
                ->comment('Resposta/Justificativa do Colaborador');

            // timestamps() adicionado para updated_at também
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};
