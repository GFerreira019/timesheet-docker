<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControleProjetoHistorico extends Model
{
    /**
     * Tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'controle_projetos_historico';

    /**
     * Desabilita os timestamps do Laravel se for o caso, 
     * mas como configuramos data_edicao, vamos lidar com isso.
     */
    public $timestamps = false;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'projeto_original_id',
        'dados_snapshot',
        'editado_por_id',
        'data_edicao',
        'numero_edicao',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dados_snapshot' => 'array',
        'data_edicao' => 'datetime',
    ];

    /**
     * Relacionamento com a obra original.
     */
    public function obraOriginal()
    {
        return $this->belongsTo(ErpObraManual::class, 'projeto_original_id');
    }

    /**
     * Relacionamento com o usuário que editou.
     */
    public function editadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'editado_por_id');
    }
}
