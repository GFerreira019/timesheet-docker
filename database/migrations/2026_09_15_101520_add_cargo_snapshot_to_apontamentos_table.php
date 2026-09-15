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
        Schema::table('apontamentos', function (Blueprint $table) {
            $table->string('cargo_snapshot')->nullable()->after('colaborador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apontamentos', function (Blueprint $table) {
            $table->dropColumn('cargo_snapshot');
        });
    }
};

