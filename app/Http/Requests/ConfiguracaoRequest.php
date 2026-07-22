<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ConfiguracaoRequest
 *
 * Valida a edição de dados de perfil do colaborador logado.
 * Equivalente à lógica de configuracoes_view() do views.py do Django
 * que atualizava os campos telefone e cargo do Colaborador.
 */
class ConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'telefone' => ['nullable', 'string', 'max:20', 'regex:/^\d{10,13}$/'],
            'cidade'   => ['nullable', 'string', 'max:100'],
            'uf'       => ['nullable', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telefone.regex' => 'Telefone inválido. Use apenas números com DDD (ex: 11987654321).',
            'uf.size'        => 'UF deve ter exatamente 2 caracteres (ex: SP, RJ).',
        ];
    }
}
