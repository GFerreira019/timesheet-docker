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
        Schema::table('produtividade_colaborador', function (Blueprint $table) {
            $table->renameColumn('cidade', 'cidade_moradia');
            $table->string('cidade_trabalho')->nullable()->after('cidade');
            $table->string('setor')->nullable()->after('cidade_trabalho');
            $table->date('data_admissao')->nullable()->after('setor_id');
            $table->date('data_demissao')->nullable()->after('data_admissao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_colaborador', function (Blueprint $table) {
            $table->dropColumn(['cidade_trabalho', 'setor', 'data_admissao', 'data_demissao']);
            $table->renameColumn('cidade_moradia', 'cidade');
        });
    }
};
