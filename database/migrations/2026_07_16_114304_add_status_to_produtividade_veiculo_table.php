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
        Schema::table('produtividade_veiculo', function (Blueprint $table) {
            $table->string('status', 20)->default('ativo');
            $table->string('sistema_rastreamento', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtividade_veiculo', function (Blueprint $table) {
            $table->dropColumn(['status', 'sistema_rastreamento']);
        });
    }
};
