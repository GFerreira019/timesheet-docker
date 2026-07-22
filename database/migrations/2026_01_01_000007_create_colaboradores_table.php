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
        // Adiciona campos extras na tabela users padrão do Laravel
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superuser')->default(false)->after('remember_token')
                ->comment('Equivalente ao is_superuser do Django — papel Owner');
        });

        // Tabela de perfil do Colaborador
        Schema::create('produtividade_colaborador', function (Blueprint $table) {
            $table->id();

            // Identificação interna da empresa
            $table->string('id_colaborador', 50)->unique()->comment('ID Colaborador (matrícula)');
            $table->string('nome_completo', 255);
            $table->string('cargo', 100)->default('Operador');

            // Dados geográficos para cálculo de feriados municipais
            $table->string('cidade', 100)->nullable()
                ->comment('Necessário para cálculo de feriados municipais');
            $table->string('uf', 2)->nullable()
                ->comment('Sigla do Estado (ex: SP, RJ)');

            // Relação OneToOne com a tabela users do Laravel
            // on_delete=SET_NULL equivale a: nullOnDelete()
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete()
                ->comment('Conta de Usuário (Login)');

            // Setor de alocação do colaborador (FK simples)
            // on_delete=SET_NULL do Django
            $table->foreignId('setor_id')->nullable()
                ->constrained('setores')->nullOnDelete()
                ->comment('Setor de Alocação');

            // Contato WhatsApp para notificações
            $table->string('telefone', 20)->nullable()
                ->comment('Formato: 19987654321');

            $table->timestamps();
        });

        /**
         * Tabela Pivô M2M: Colaborador ↔ Setores Gerenciados
         * Equivalente ao Django: setores_gerenciados = ManyToManyField(Setor, related_name='gestores')
         * Nomear como colaborador_setor_gerenciado para não colidir com outras pivôs.
         */
        Schema::create('colaborador_setor_gerenciado', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->primary(['colaborador_id', 'setor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_setor_gerenciado');
        Schema::dropIfExists('colaboradores');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superuser');
        });
    }
};
