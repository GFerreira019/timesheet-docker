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
        'is_ajustado',
        'justificativa',
        'horas_abonadas',
        'dia_trabalhado',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    public function getHorasAbonadasFormatadasAttribute(): string
    {
        if (!$this->horas_abonadas) {
            return '-';
        }
        
        $horasDecimal = (float) $this->horas_abonadas;
        
        // Normaliza o erro de parse do JSON da API (ex: 2683.33 vira 2.68333)
        if ($horasDecimal > 100) {
            $horasDecimal = $horasDecimal / 1000;
        }
        
        $horas = floor($horasDecimal);
        $minutos = round(($horasDecimal - $horas) * 60);
        
        return sprintf('%02d:%02d', $horas, $minutos);
    }
}
