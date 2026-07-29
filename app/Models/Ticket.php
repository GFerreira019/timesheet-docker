<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'categoria', 'prioridade', 'titulo', 'descricao', 'status', 'anexo_path'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
