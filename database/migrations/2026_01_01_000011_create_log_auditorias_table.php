<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: LogAuditoria
 * Tabela central de auditoria — rastreia todas as ações críticas do sistema.
 * Esta tabela é APPEND-ONLY (registros não são alterados após criação).
 *
 * ACAO_CHOICES: LOGIN | LOGOUT | LOGIN_FALHA | CRIACAO | EDICAO | EXCLUSAO |
 *               APROVACAO | REJEICAO | SOLICITACAO | APROVACAO_AJUSTE | EXPORTACAO
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_auditorias', function (Blueprint $table) {
            $table->id();

            // Usuário responsável — nullable: sistema pode gerar logs sem usuário (ex: aprovação automática)
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Usuário Responsável');

            // Ação realizada (db_index=True no Django)
            $table->string('acao', 20)->index()
                ->comment("LOGIN | LOGOUT | LOGIN_FALHA | CRIACAO | EDICAO | EXCLUSAO | APROVACAO | REJEICAO | SOLICITACAO | APROVACAO_AJUSTE | EXPORTACAO");

            $table->string('modelo_afetado', 50)->comment('Módulo/Tabela afetada');

            // ID do objeto afetado (string para flexibilidade)
            $table->string('objeto_id', 50)->nullable()->comment('ID do Objeto Afetado');

            $table->text('detalhes')->nullable()->comment('Detalhes da Ação');

            // IP do cliente (suporta IPv4 e IPv6)
            $table->ipAddress('ip_address')->nullable()->comment('Endereço IP');

            // data_hora: auto_now_add + db_index no Django
            $table->timestamp('data_hora')->useCurrent()->index()
                ->comment('Data e Hora do Evento');

            // Índice composto equivalente ao Meta.indexes do Django
            $table->index(['data_hora', 'acao']);

            // Não usa updated_at — logs são imutáveis
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_auditorias');
    }
};
