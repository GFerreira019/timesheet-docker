<?php

namespace App\Http\Controllers;

use App\Models\Setor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SetorController extends Controller
{
    /**
     * Lista todos os setores.
     */
    public function index()
    {
        $setores = Setor::orderBy('nome')->get();
        return view('setores.index', compact('setores'));
    }

    /**
     * Armazena um novo setor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:setores,nome'],
        ]);

        $validated['ativo'] = true; // Por padrão, ao criar é ativo

        Setor::create($validated);

        return redirect()->route('setores.index')->with('success', 'Setor cadastrado com sucesso!');
    }

    /**
     * Atualiza o setor.
     */
    public function update(Request $request, Setor $setor)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', Rule::unique('setores')->ignore($setor->id)],
        ]);

        $setor->update($validated);

        return redirect()->route('setores.index')->with('success', 'Setor atualizado com sucesso!');
    }

    /**
     * Alterna o status do setor (ativo/inativo).
     */
    public function toggleStatus(Setor $setor)
    {
        $setor->update([
            'ativo' => !$setor->ativo
        ]);

        $status = $setor->ativo ? 'ativado' : 'inativado';
        return redirect()->route('setores.index')->with('success', "Setor {$status} com sucesso!");
    }
}
