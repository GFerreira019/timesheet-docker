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
            $table->dropColumn(['id_colaborador', 'nivel_acesso']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_colaborador', function (Blueprint $table) {
            $table->string('id_colaborador')->nullable()->unique();
            $table->string('nivel_acesso', 20)->default('OPERACIONAL');
        });
    }
};
