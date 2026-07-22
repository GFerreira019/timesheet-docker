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
        Schema::create('erp_obras_mock', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_codigo');
            $table->string('projeto_codigo');
            $table->string('projeto_nome');
            $table->boolean('status_ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_obras_mock');
    }
};
