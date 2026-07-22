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
            $table->date('data_vigencia')->nullable()->after('data_demissao');
        });

        Schema::table('colaborador_historicos', function (Blueprint $table) {
            $table->date('data_vigencia')->nullable()->after('campos_alterados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_colaborador', function (Blueprint $table) {
            $table->dropColumn('data_vigencia');
        });

        Schema::table('colaborador_historicos', function (Blueprint $table) {
            $table->dropColumn('data_vigencia');
        });
    }
};
