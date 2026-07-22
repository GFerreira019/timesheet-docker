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
        Schema::table('notificacoes', function (Blueprint $table) {
            $table->foreignId('apontamento_id')->nullable()->constrained('apontamentos')->nullOnDelete()
                ->comment('ID do apontamento relacionado (opcional)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificacoes', function (Blueprint $table) {
            $table->dropForeign(['apontamento_id']);
            $table->dropColumn('apontamento_id');
        });
    }
};
