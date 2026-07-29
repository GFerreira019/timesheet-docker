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
            // Removemos de uma vez todas as colunas não utilizadas no cenário de SSO
            $table->dropColumn([
                'is_superuser',
                'password',
                'remember_token',
                'email_verified_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Recriando as colunas caso você precise fazer um rollback no futuro
            $table->tinyInteger('is_superuser')->default(0);
            $table->string('password');
            $table->rememberToken(); // Método nativo que recria a remember_token
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};
