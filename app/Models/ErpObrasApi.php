<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpObrasApi extends Model
{
    /**
     * Tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'erp_obras_api';

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cliente_codigo',
        'projeto_codigo',
        'projeto_nome',
        'status_ativo',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_ativo' => 'boolean',
    ];
}
