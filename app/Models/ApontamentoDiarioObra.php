<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApontamentoDiarioObra extends Model
{
    protected $table = 'apontamento_diario_obras';

    protected $fillable = [
        'apontamento_id',
        'texto_diario',
    ];

    public function apontamento()
    {
        return $this->belongsTo(Apontamento::class);
    }
}
