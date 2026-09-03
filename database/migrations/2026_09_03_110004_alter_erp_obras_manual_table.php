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
        Schema::table('erp_obras_manual', function (Blueprint $table) {
            $table->unique(['cliente_codigo', 'projeto_codigo', 'projeto_unidade', 'cnpj'], 'erp_obras_manual_composite_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_obras_manual', function (Blueprint $table) {
            $table->dropUnique('erp_obras_manual_composite_unique');
        });
    }
};
