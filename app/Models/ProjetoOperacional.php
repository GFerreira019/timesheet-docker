<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjetoOperacional extends Model
{
    protected $table = 'projetos_operacionais';
    protected $guarded = [];

    public function cliente()
    {
        return $this->belongsTo(ClienteOperacional::class, 'cliente_operacional_id');
    }

    public function getNomeAttribute(): string
    {
        return $this->loadMissing('cliente')->cliente ? $this->cliente->nome : 'N/A';
    }

    public function gestores()
    {
        return $this->belongsToMany(
            Colaborador::class,
            'colaborador_projeto_gerenciado',
            'projeto_operacional_id',
            'colaborador_id'
        )->using(\App\Models\Pivots\ColaboradorProjetoPivot::class)
         ->withPivot('implantacao', 'manutencao')
         ->withTimestamps();
    }
}
