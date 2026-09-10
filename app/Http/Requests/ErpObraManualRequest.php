<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ErpObraManualRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Remove máscaras dos campos de valor antes da validação
        $valores = [
            'valor_venda', 
            'valor_monitoramento', 
            'valor_licenca', 
            'valor_manutencao', 
            'valor_locacao'
        ];

        $mergeData = [];
        foreach ($valores as $campo) {
            if ($this->has($campo) && !empty($this->input($campo))) {
                // R$ 1.500,00 -> 1500.00
                $valorLimpo = preg_replace('/[^0-9,-]/', '', $this->input($campo));
                $valorLimpo = str_replace(',', '.', $valorLimpo);
                $mergeData[$campo] = $valorLimpo ?: null;
            } else {
                $mergeData[$campo] = null;
            }
        }

        $this->merge($mergeData);

        // Tratamento da máscara do CNPJ
        if ($this->has('cnpj') && !empty($this->input('cnpj'))) {
            $mergeData['cnpj'] = preg_replace('/[^0-9]/', '', $this->input('cnpj'));
        }

        // Sanitização da Unidade: não pode ser nula para não quebrar a unique constraint
        if (empty($this->input('projeto_unidade'))) {
            $mergeData['projeto_unidade'] = 'N/A';
        }

        // Extração automática do cliente_codigo a partir do projeto_codigo
        if ($this->has('projeto_codigo') && strlen($this->projeto_codigo) >= 5) {
            $mergeData['cliente_codigo'] = substr($this->projeto_codigo, 1, 4);
        }
        
        $mergeData['ausencia_cronograma'] = $this->boolean('ausencia_cronograma');
        $mergeData['ausencia_contrato'] = $this->boolean('ausencia_contrato');
        $mergeData['ausencia_termo'] = $this->boolean('ausencia_termo');
        
        // Tratamento do Target que vem do input type="month" (YYYY-MM)
        if ($this->has('target') && !empty($this->input('target'))) {
            $target = $this->input('target');
            if (strlen($target) === 7) {
                $mergeData['target'] = $target . '-01';
            }
        }
        
        // Tratamento da Etapa (se vier como array do multi-select, salva concatenado por ' - ')
        if ($this->has('projeto_etapa')) {
            $etapaInput = $this->input('projeto_etapa');
            if (is_array($etapaInput)) {
                $etapaFiltrada = array_filter(array_map('trim', $etapaInput));
                $mergeData['projeto_etapa'] = !empty($etapaFiltrada) ? implode(' - ', $etapaFiltrada) : null;
            }
        }
        
        $this->merge($mergeData);
    }

    public function rules()
    {
        $id = $this->route('id') ?? $this->route('erp_obras_manual') ?? $this->route('obra');

        $uniqueRule = \Illuminate\Validation\Rule::unique('erp_obras_manual', 'projeto_codigo')
            ->where(function ($query) {
                return $query->where('cliente_codigo', $this->cliente_codigo)
                             ->where('projeto_unidade', $this->projeto_unidade)
                             ->where('cnpj', $this->cnpj);
            });

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $uniqueRule->ignore($id);
        }

        return [
            // Dados Gerais
            'projeto_codigo' => [
                'required',
                'string',
                'max:8',
                $uniqueRule
            ],
            'projeto_nome' => 'required|string|max:255',
            'cliente_codigo' => 'nullable|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:255',
            'endereco' => 'nullable|string',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            
            'tipo_categoria' => 'nullable|string|in:CONTRATO,PROPOSTA',
            
            // Controle e Cronograma
            'projeto_unidade' => 'nullable|string|max:100',
            'projeto_objeto' => 'nullable|string|max:255',
            'projeto_etapa' => 'nullable|string|max:255',
            'projeto_status' => 'nullable|string|max:50',
            'projeto_avanco' => 'nullable|numeric|min:0|max:100',
            'ausencia_cronograma' => 'boolean',
            'cronograma_inicio' => 'nullable|date',
            'cronograma_fim' => 'nullable|date',
            'setor_id' => 'nullable|integer|exists:setores,id',
            'centro_custo' => 'nullable|string|max:255',
            'target' => 'nullable|date',
            'ausencia_contrato' => 'boolean',
            'contrato_assinatura' => 'nullable|date',
            'ausencia_termo' => 'boolean',
            'termo_entrega' => 'nullable|date',
            'pedagio' => 'boolean',
            'comentarios' => 'nullable|string',

            // Gestores
            'lider_comercial' => 'nullable|integer|exists:produtividade_colaborador,id',
            'coordenadores_implantacao' => 'nullable|array',
            'coordenadores_implantacao.*' => 'integer|exists:produtividade_colaborador,id',
            'coordenadores_manutencao' => 'nullable|array',
            'coordenadores_manutencao.*' => 'integer|exists:produtividade_colaborador,id',

            // Financeiro
            'valor_venda' => 'nullable|numeric',
            'valor_monitoramento' => 'nullable|numeric',
            'valor_licenca' => 'nullable|numeric',
            'valor_manutencao' => 'nullable|numeric',
            'valor_locacao' => 'nullable|numeric',
            
            'status_ativo' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'projeto_codigo.unique' => 'Esta obra já está cadastrada. Verifique o código, unidade e CNPJ ou acesse a edição.',
        ];
    }
}
