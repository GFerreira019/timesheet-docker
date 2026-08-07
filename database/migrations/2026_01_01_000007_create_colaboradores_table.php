<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: Colaborador
 * Entidade que estende o User do Laravel com regras de negócio.
 *
 * Decisão de design: mantida a relação OneToOne com a tabela users padrão
 * do Laravel, espelhando o user_account = OneToOneField(User) do Django.
 *
 * RBAC: a tabela users padrão do Laravel possui o campo `is_superuser`
 * adicionado aqui. Os grupos (GESTOR, ADMINISTRATIVO, COORDENADOR) serão
 * gerenciados pelo pacote spatie/laravel-permission (Etapa 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabela de perfil do Colaborador
        Schema::create('produtividade_colaborador', function (Blueprint $table) {
            $table->id();

            // Identificação interna da empresa (chave de ligação com o ERP)
            $table->string('id_colaborador', 50)->unique()->comment('ID Colaborador (matrícula / chave do ERP)');
            $table->string('nome_completo', 255)->nullable();
            $table->string('nivel_acesso', 20)->default('OPERACIONAL');
            $table->string('cargo', 100)->nullable();

            // Dados geográficos para cálculo de feriados municipais
            $table->string('cidade_moradia', 100)->nullable()
                ->comment('Necessário para cálculo de feriados municipais');
            $table->string('cidade_trabalho')->nullable();
            $table->string('setor')->nullable();
            $table->string('uf', 2)->nullable()
                ->comment('Sigla do Estado (ex: SP, RJ)');

            // Setor de alocação do colaborador (FK simples)
            $table->foreignId('setor_id')->nullable()
                ->constrained('setores')->nullOnDelete()
                ->comment('Setor de Alocação');
            $table->date('data_admissao')->nullable();
            $table->date('data_demissao')->nullable();
            $table->date('data_vigencia')->nullable();

            // Contato WhatsApp para notificações
            $table->string('telefone', 20)->nullable()
                ->comment('Formato: 19987654321');

            $table->timestamps();
        });

        // Após criar produtividade_colaborador, adiciona a FK de users → colaborador
        // Assim garantimos integridade referencial sem dependência circular
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('produtividade_colaborador_id')
                ->references('id')
                ->on('produtividade_colaborador')
                ->nullOnDelete();
        });

        /**
         * Tabela Pivô M2M: Colaborador ↔ Setores Gerenciados
         * Equivalente ao Django: setores_gerenciados = ManyToManyField(Setor, related_name='gestores')
         * Nomear como colaborador_setor_gerenciado para não colidir com outras pivôs.
         */
        Schema::create('colaborador_setor_gerenciado', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->constrained('produtividade_colaborador')->cascadeOnDelete();
            $table->foreignId('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->primary(['colaborador_id', 'setor_id']);
        });
    }

    public function down(): void
    {
        // Remove a FK de users antes de derrubar a tabela referenciada
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['produtividade_colaborador_id']);
        });

        Schema::dropIfExists('colaborador_setor_gerenciado');
        Schema::dropIfExists('produtividade_colaborador');
    }
};
