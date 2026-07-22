<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: Apontamento
 * Tabela CORE do sistema — Registro principal de Timesheet.
 *
 * Grupos de campos replicados fielmente:
 * 1. Identificação e Tempo
 * 2. Localização e Contexto
 * 3. Gestão de Veículos (Híbrida: FK cadastrada OU campos manuais)
 * 4. Equipe e Ocorrências
 * 5. Adicionais de Folha (Plantão, Dorme Fora)
 * 6. Auditoria (Criação)
 * 7. Controle de Ajustes e Workflow
 * 8. Geolocalização
 * 9. Controle de Alertas CLT
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apontamentos', function (Blueprint $table) {
            $table->id();

            // ---------------------------------------------------------------
            // 1. Identificação e Tempo
            // ---------------------------------------------------------------
            $table->foreignId('colaborador_id')
                ->constrained('produtividade_colaborador')->restrictOnDelete()
                ->comment('PROTECT no Django — não permite excluir colaborador com apontamentos');
            $table->date('data_apontamento')->comment('Data');
            $table->time('hora_inicio')->comment('Hora Início');
            $table->time('hora_termino')->nullable()->comment('Hora Término — null = check-in ativo');

            // ---------------------------------------------------------------
            // 2. Localização e Contexto
            // ---------------------------------------------------------------
            // LOCAL_CHOICES: 'INT' = Dentro da obra | 'EXT' = Fora da obra
            $table->string('local_execucao', 3)->default('INT')
                ->comment("INT = Dentro da obra | EXT = Fora da obra");

            $table->foreignId('projeto_id')->nullable()
                ->constrained('produtividade_projeto')->nullOnDelete();
                
            $table->foreignId('codigo_cliente_id')->nullable()
                ->constrained('produtividade_codigocliente')->nullOnDelete();
                
            $table->foreignId('centro_custo_id')->nullable()
                ->constrained('produtividade_centrocusto')->nullOnDelete()
                ->comment('Setor / Justificativa (Custo)');

            // ---------------------------------------------------------------
            // 3. Gestão de Veículos (Híbrida)
            // ---------------------------------------------------------------
            $table->foreignId('veiculo_id')->nullable()
                ->constrained('produtividade_veiculo')->nullOnDelete()
                ->comment('Veículo Cadastrado (Frota)');
            $table->string('veiculo_manual_modelo', 100)->nullable()->comment('Modelo (Manual)');
            $table->string('veiculo_manual_placa', 20)->nullable()->comment('Placa (Manual)');

            // ---------------------------------------------------------------
            // 4. Equipe e Ocorrências
            // ---------------------------------------------------------------
            $table->text('ocorrencias')->nullable()->comment('Ocorrências / Obs.');

            // Auxiliar principal (FK simples para Colaborador)
            // related_name='apontamentos_auxiliados' no Django
            $table->foreignId('auxiliar_id')->nullable()
                ->constrained('produtividade_colaborador')->nullOnDelete();

            // Auxiliares extras são gerenciados na tabela pivô abaixo (M2M)

            // ---------------------------------------------------------------
            // 5. Adicionais de Folha
            // ---------------------------------------------------------------
            $table->boolean('em_plantao')->default(false)->comment('Atividade em Plantão?');
            $table->date('data_plantao')->nullable()->comment('Data do Plantão');
            $table->boolean('dorme_fora')->default(false)->comment('Dorme Fora Nesta Data?');
            $table->date('data_dorme_fora')->nullable()->comment('Data do Dorme-Fora');

            // ---------------------------------------------------------------
            // 6. Auditoria (Criação)
            // ---------------------------------------------------------------
            $table->foreignId('registrado_por_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Usuário de Registro (FK para users)');
            $table->timestamp('data_registro')->useCurrent()
                ->comment('Equivalente ao auto_now_add do Django');

            // ---------------------------------------------------------------
            // 7. Controle de Ajustes e Workflow
            // ---------------------------------------------------------------
            $table->string('id_agrupamento', 100)->nullable()
                ->comment('UUID para agrupar registros de rateio');

            $table->text('motivo_ajuste')->nullable()
                ->comment('Motivo do Ajuste (Solicitação)');

            // STATUS_APROVACAO_CHOICES
            $table->string('status_aprovacao', 20)->default('EM_ANALISE')
                ->comment("EM_ANALISE | APROVADO | REJEITADO | SOLICITACAO_AJUSTE");

            // Status legado de ajuste
            $table->string('status_ajuste', 20)->nullable()
                ->comment("PENDENTE | APROVADO — campo legado");

            $table->integer('contagem_edicao')->default(0)
                ->comment('Qtd. Edições Realizadas — limite de 1 para colaboradores comuns');

            $table->text('motivo_rejeicao')->nullable()
                ->comment('Motivo da Rejeição (Gerente)');

            // ---------------------------------------------------------------
            // 8. Geolocalização
            // ---------------------------------------------------------------
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();

            // ---------------------------------------------------------------
            // 9. Controle de Alertas CLT
            // ---------------------------------------------------------------
            $table->boolean('flag_atencao')->default(false)
                ->comment('Atenção Conformidade CLT');
            $table->text('motivo_alerta')->nullable()
                ->comment('Motivo do Alerta CLT (gerado automaticamente)');

            $table->timestamps();

            // ---------------------------------------------------------------
            // Indexes (equivalente ao Meta.indexes do Django)
            // ---------------------------------------------------------------
            $table->index(['colaborador_id', 'data_apontamento']);
            $table->index('data_apontamento');
            $table->index('status_aprovacao');
        });

        /**
         * Tabela Pivô M2M: Apontamento ↔ Colaboradores (Auxiliares Extras)
         * Equivalente ao Django: auxiliares_extras = ManyToManyField(Colaborador, related_name='apontamentos_como_extra')
         */
        Schema::create('apontamento_auxiliar_extra', function (Blueprint $table) {
            $table->foreignId('apontamento_id')->constrained('apontamentos')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->constrained('produtividade_colaborador')->cascadeOnDelete();
            $table->primary(['apontamento_id', 'colaborador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apontamento_auxiliar_extra');
        Schema::dropIfExists('apontamentos');
    }
};
