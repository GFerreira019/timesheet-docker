<?php

namespace App\Observers;

use App\Models\ErpObraManual;
use App\Models\ControleProjetoHistorico;

class ErpObraManualObserver
{
    /**
     * Handle the ErpObraManual "saved" event.
     * We use 'saved' to catch both creates and updates if needed, 
     * but the prompt specifically mentions capturing old/new data on update.
     * I'll use 'updated' to specifically audit changes.
     *
     * @param  \App\Models\ErpObraManual  $obra
     * @return void
     */
    public function updated(ErpObraManual $obra)
    {
        $snapshot = $obra->toArray();
        $snapshot['setores_vinculados'] = $obra->setores->map(function($setor) {
            return [
                'id' => $setor->id,
                'nome' => $setor->nome,
                'status' => $setor->pivot->status,
                'data_alteracao' => $setor->pivot->data_alteracao,
                'ativo' => $setor->pivot->ativo,
            ];
        })->toArray();

        if (empty($snapshot['setores_vinculados']) && request()->has('setores')) {
            $reqSetores = request('setores');
            $ids = collect($reqSetores)->pluck('id')->filter()->toArray();
            $nomes = \App\Models\Setor::whereIn('id', $ids)->pluck('nome', 'id');
            foreach ($reqSetores as $s) {
                if (!empty($s['id'])) {
                    $snapshot['setores_vinculados'][] = [
                        'id' => $s['id'],
                        'nome' => $nomes[$s['id']] ?? 'Desconhecido',
                        'status' => $s['status'] ?? null,
                        'data_alteracao' => $s['data_alteracao'] ?? null,
                        'ativo' => in_array($s['status'] ?? null, ['EM ANDAMENTO', 'SEM STATUS', 'SEM TARGET']),
                    ];
                }
            }
        }

        $ultimaEdicao = ControleProjetoHistorico::where('projeto_original_id', $obra->id)
            ->orderBy('numero_edicao', 'desc')
            ->first();

        $proximoNumero = $ultimaEdicao ? ($ultimaEdicao->numero_edicao + 1) : 1;

        ControleProjetoHistorico::create([
            'projeto_original_id' => $obra->id,
            'dados_snapshot' => $snapshot,
            'editado_por_id' => auth()->id(), // Pega o usuário logado
            'numero_edicao' => $proximoNumero,
            'data_edicao' => now(),
        ]);

        $this->syncToProdutividade($obra);
    }
    
    /**
     * Handle the ErpObraManual "created" event.
     * Optionally log the initial creation as edition 1.
     *
     * @param  \App\Models\ErpObraManual  $obra
     * @return void
     */
    public function created(ErpObraManual $obra)
    {
        $snapshot = $obra->toArray();
        $snapshot['setores_vinculados'] = $obra->setores->map(function($setor) {
            return [
                'id' => $setor->id,
                'nome' => $setor->nome,
                'status' => $setor->pivot->status,
            ];
        })->toArray();

        if (empty($snapshot['setores_vinculados']) && request()->has('setores')) {
            $reqSetores = request('setores');
            $ids = collect($reqSetores)->pluck('id')->filter()->toArray();
            $nomes = \App\Models\Setor::whereIn('id', $ids)->pluck('nome', 'id');
            foreach ($reqSetores as $s) {
                if (!empty($s['id'])) {
                    $snapshot['setores_vinculados'][] = [
                        'id' => $s['id'],
                        'nome' => $nomes[$s['id']] ?? 'Desconhecido',
                        'status' => $s['status'] ?? null,
                    ];
                }
            }
        }

        ControleProjetoHistorico::create([
            'projeto_original_id' => $obra->id,
            'dados_snapshot' => $snapshot,
            'editado_por_id' => auth()->id(),
            'numero_edicao' => 1,
            'data_edicao' => now(),
        ]);

        $this->syncToProdutividade($obra);
    }

    /**
     * Sincroniza a obra manual com as tabelas de produtividade legadas.
     *
     * @param  \App\Models\ErpObraManual  $obra
     * @return void
     */
    private function syncToProdutividade(ErpObraManual $obra)
    {
        // Etapa A: Sincroniza Cliente (apenas dados cadastrais)
        $cliente = \App\Models\CodigoCliente::updateOrCreate(
            [
                'codigo' => $obra->cliente_codigo,
                'cnpj'   => $obra->cnpj,
            ],
            [
                'nome'  => $obra->projeto_nome,
                // O status 'ativo' será recalculado na Etapa C
            ]
        );

        // Etapa B: Sincroniza Projeto (Obra)
        $unidade = $obra->projeto_unidade ?: 'N/A';

        \App\Models\Projeto::updateOrCreate(
            [
                'codigo_cliente_id' => $cliente->id,
                'codigo'            => $obra->projeto_codigo,
                'unidade'           => $unidade,
            ],
            [
                'ativo'             => $obra->status_ativo,
            ]
        );

        // Etapa C: Recálculo Dinâmico do Status do Cliente
        $temProjetoAtivo = \App\Models\Projeto::where('codigo_cliente_id', $cliente->id)
            ->where('ativo', 1)
            ->exists();

        $cliente->update(['ativo' => $temProjetoAtivo ? 1 : 0]);

        // Etapa D: Sincroniza Cliente Operacional (Visão Operacional)
        $clienteOperacional = \App\Models\ClienteOperacional::updateOrCreate(
            [
                'codigo' => $obra->cliente_codigo,
            ],
            [
                'nome'  => $obra->projeto_nome,
                // O status 'ativo' será recalculado no final da etapa E
            ]
        );

        // Etapa E: Sincroniza Projeto Operacional (Visão Operacional)
        \App\Models\ProjetoOperacional::updateOrCreate(
            [
                'cliente_operacional_id' => $clienteOperacional->id,
                'codigo'                 => $obra->projeto_codigo,
                'unidade'                => $unidade,
            ],
            [
                'ativo'                  => $obra->status_ativo,
            ]
        );

        // Recálculo Dinâmico do Status do Cliente Operacional
        $temProjetoOperacionalAtivo = \App\Models\ProjetoOperacional::where('cliente_operacional_id', $clienteOperacional->id)
            ->where('ativo', 1)
            ->exists();

        $clienteOperacional->update(['ativo' => $temProjetoOperacionalAtivo ? 1 : 0]);
    }
}
