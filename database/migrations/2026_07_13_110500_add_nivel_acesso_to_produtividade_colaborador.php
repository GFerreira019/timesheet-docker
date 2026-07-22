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
            $table->string('nivel_acesso', 20)->default('OPERACIONAL')->after('nome_completo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_colaborador', function (Blueprint $table) {
            $table->dropColumn('nivel_acesso');
        });
    }
};
