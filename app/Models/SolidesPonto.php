<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolidesPonto extends Model
{
    protected $table = 'solides_pontos';

    protected $fillable = [
        'solides_ponto_id',
        'colaborador_id',
        'data',
        'hora_entrada',
        'hora_saida',
        'status',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }
}
