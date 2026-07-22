<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Renomeia a tabela atual para notificacoes_old
        Schema::rename('notificacoes', 'notificacoes_old');

        // 2. Cria a tabela notificacoes com a Foreign Key correta
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();

            // Correção da Foreign Key apontando para produtividade_colaborador
            $table->foreignId('colaborador_id')
                ->constrained('produtividade_colaborador')->cascadeOnDelete()
                ->comment('Destinatário');

            $table->string('titulo', 100)->comment('Título');
            $table->text('mensagem')->comment('Conteúdo da Mensagem');

            $table->string('tipo', 10)->default('INFO')
                ->comment('ALERTA | INFO | SUCESSO');

            $table->boolean('lida')->default(false)->comment('Lida?');

            $table->timestamp('data_criacao')->useCurrent();

            $table->date('data_referencia')->nullable()
                ->comment('Data de Referência (Para Alertas)');

            $table->text('comentario_colaborador')->nullable()
                ->comment('Resposta/Justificativa do Colaborador');

            $table->timestamp('updated_at')->nullable();
        });

        // 3. Copia os dados da tabela antiga para a nova
        DB::statement('INSERT INTO notificacoes SELECT * FROM notificacoes_old');

        // 4. Exclui a tabela antiga
        Schema::dropIfExists('notificacoes_old');
    }

    public function down(): void
    {
        Schema::rename('notificacoes', 'notificacoes_old');

        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();

            // Recria com a FK errada antiga para rollback
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')->cascadeOnDelete()
                ->comment('Destinatário');

            $table->string('titulo', 100)->comment('Título');
            $table->text('mensagem')->comment('Conteúdo da Mensagem');
            $table->string('tipo', 10)->default('INFO')->comment('ALERTA | INFO | SUCESSO');
            $table->boolean('lida')->default(false)->comment('Lida?');
            $table->timestamp('data_criacao')->useCurrent();
            $table->date('data_referencia')->nullable()->comment('Data de Referência (Para Alertas)');
            $table->text('comentario_colaborador')->nullable()->comment('Resposta/Justificativa do Colaborador');
            $table->timestamp('updated_at')->nullable();
        });

        DB::statement('INSERT INTO notificacoes SELECT * FROM notificacoes_old');
        Schema::dropIfExists('notificacoes_old');
    }
};
