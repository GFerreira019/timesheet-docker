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
            $table->boolean('recebe_notificacao')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_colaborador', function (Blueprint $table) {
            $table->dropColumn('recebe_notificacao');
        });
    }
};
