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
        Schema::table('produtividade_codigocliente', function (Blueprint $table) {
            $table->dropUnique('produtividade_codigocliente_codigo_unique');
            $table->string('cnpj', 20)->nullable();
            $table->unique(['codigo', 'cnpj']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_codigocliente', function (Blueprint $table) {
            $table->dropUnique(['codigo', 'cnpj']);
            $table->dropColumn('cnpj');
            $table->unique('codigo', 'produtividade_codigocliente_codigo_unique');
        });
    }
};
