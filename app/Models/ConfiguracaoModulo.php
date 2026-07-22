<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoModulo extends Model
{
    protected $table = 'configuracoes_modulo';

    protected $fillable = [
        'chave',
        'valor',
    ];

    /**
     * Helper para obter um valor pela chave.
     */
    public static function get(string $chave, $default = null)
    {
        $config = self::where('chave', $chave)->first();
        return $config ? $config->valor : $default;
    }

    /**
     * Helper para definir um valor pela chave.
     */
    public static function set(string $chave, string $valor)
    {
        return self::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
