<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. apontamentos_archive
        Schema::create('apontamentos_archive', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('colaborador_id');
            $table->date('data_apontamento');
            $table->time('hora_inicio');
            $table->time('hora_termino')->nullable();

            $table->string('local_execucao', 7)->default('EXTERNO');
            $table->unsignedBigInteger('projeto_id')->nullable();
            $table->unsignedBigInteger('codigo_cliente_id')->nullable();
            $table->unsignedBigInteger('centro_custo_id')->nullable();

            $table->unsignedBigInteger('veiculo_id')->nullable();
            $table->string('veiculo_manual_modelo', 100)->nullable();
            $table->string('veiculo_manual_placa', 20)->nullable();

            $table->text('ocorrencias')->nullable();
            $table->unsignedBigInteger('auxiliar_id')->nullable();

            $table->boolean('em_plantao')->default(false);
            $table->date('data_plantao')->nullable();
            $table->boolean('dorme_fora')->default(false);
            $table->date('data_dorme_fora')->nullable();

            $table->unsignedBigInteger('registrado_por_id')->nullable();
            $table->timestamp('data_registro')->useCurrent();

            $table->uuid('id_agrupamento')->nullable();
            $table->text('motivo_ajuste')->nullable();
            $table->string('status_aprovacao', 20)->default('EM_ANALISE');
            $table->string('tipo_aprovacao', 20)->nullable()->default(null);
            $table->unsignedBigInteger('aprovador_id')->nullable();
            $table->timestamp('data_aprovacao')->nullable()->default(null);
            $table->string('status_ajuste', 20)->nullable();
            $table->integer('contagem_edicao')->default(0);
            $table->text('motivo_rejeicao')->nullable();

            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->boolean('flag_atencao')->default(false);
            $table->text('motivo_alerta')->nullable();

            $table->timestamps();

            // Coluna para manter dados de referência
            $table->json('snapshot_dados')->nullable();

            // Índices para a tabela de arquivo
            $table->index(['colaborador_id', 'data_apontamento']);
            $table->index('data_apontamento');
        });

        // 2. apontamento_auxiliar_extra_archive
        Schema::create('apontamento_auxiliar_extra_archive', function (Blueprint $table) {
            $table->unsignedBigInteger('apontamento_id');
            $table->unsignedBigInteger('colaborador_id');
            $table->primary(['apontamento_id', 'colaborador_id']);

            $table->json('snapshot_dados')->nullable();
        });

        // 3. log_auditorias_archive
        Schema::create('log_auditorias_archive', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('acao', 20)->index();
            $table->string('modelo_afetado', 50);
            $table->string('objeto_id', 50)->nullable();
            $table->text('detalhes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('data_hora')->useCurrent()->index();

            $table->json('snapshot_dados')->nullable();

            $table->index(['data_hora', 'acao']);
        });

        // 4. notificacoes_archive
        Schema::create('notificacoes_archive', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('colaborador_id');
            $table->unsignedBigInteger('remetente_id')->nullable();
            $table->unsignedBigInteger('apontamento_id')->nullable();

            $table->string('titulo', 100);
            $table->text('mensagem');
            $table->string('tipo', 10)->default('INFO');
            $table->boolean('lida')->default(false);
            $table->date('data_referencia')->nullable();
            $table->text('comentario_colaborador')->nullable();

            $table->timestamps();

            $table->json('snapshot_dados')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes_archive');
        Schema::dropIfExists('log_auditorias_archive');
        Schema::dropIfExists('apontamento_auxiliar_extra_archive');
        Schema::dropIfExists('apontamentos_archive');
    }
};
