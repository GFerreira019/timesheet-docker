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
        Schema::table('produtividade_projeto', function (Blueprint $table) {
            $table->unsignedBigInteger('codigo_cliente_id')->nullable()->after('id');
            $table->foreign('codigo_cliente_id')->references('id')->on('produtividade_codigocliente')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_projeto', function (Blueprint $table) {
            $table->dropForeign(['codigo_cliente_id']);
            $table->dropColumn('codigo_cliente_id');
        });
    }
};
