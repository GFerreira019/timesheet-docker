<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration equivalente ao model Django: CentroCusto
 * Entidade para alocação de custos e justificativas operacionais.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtividade_centrocusto', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique()->comment('Nome do Centro de Custo / Justificativa');
            $table->boolean('permite_alocacao')->default(false)
                ->comment('Se marcado, ao selecionar este item, será solicitado o Código da Obra ou Cliente.');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtividade_centrocusto');
    }
};
