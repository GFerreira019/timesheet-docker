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
        Schema::table('colaborador_projeto_gerenciado', function (Blueprint $table) {
            $table->boolean('implantacao')->default(false)->after('projeto_operacional_id');
            $table->boolean('manutencao')->default(false)->after('implantacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colaborador_projeto_gerenciado', function (Blueprint $table) {
            $table->dropColumn(['implantacao', 'manutencao']);
        });
    }
};

