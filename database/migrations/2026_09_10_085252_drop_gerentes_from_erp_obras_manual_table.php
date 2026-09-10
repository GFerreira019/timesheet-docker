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
            $table->dropColumn(['gerente_implantacao', 'gerente_manutencao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_obras_manual', function (Blueprint $table) {
            $table->unsignedBigInteger('gerente_implantacao')->nullable();
            $table->unsignedBigInteger('gerente_manutencao')->nullable();
        });
    }
};
