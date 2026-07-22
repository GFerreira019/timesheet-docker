<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apontamentos', function (Blueprint $table) {
            $table->string('tipo_aprovacao', 20)->nullable()->default(null)
                ->comment("'automatica' | 'manual'")
                ->after('status_aprovacao');

            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Usuário que aprovou manualmente (null = automático)')
                ->after('tipo_aprovacao');

            $table->timestamp('data_aprovacao')->nullable()->default(null)
                ->comment('Data e hora da aprovação')
                ->after('aprovador_id');
        });
    }

    public function down(): void
    {
        Schema::table('apontamentos', function (Blueprint $table) {
            $table->dropForeign(['aprovador_id']);
            $table->dropColumn(['tipo_aprovacao', 'aprovador_id', 'data_aprovacao']);
        });
    }
};
