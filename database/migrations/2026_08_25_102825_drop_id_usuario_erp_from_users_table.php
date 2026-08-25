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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'id_usuario_erp') && Schema::hasColumn('users', 'connect_user_id')) {
                \Illuminate\Support\Facades\DB::statement('UPDATE users SET connect_user_id = id_usuario_erp WHERE id_usuario_erp IS NOT NULL');
                $table->dropUnique('users_id_usuario_erp_unique');
                $table->dropColumn('id_usuario_erp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_usuario_erp')->nullable()->unique();
        });
    }
};
