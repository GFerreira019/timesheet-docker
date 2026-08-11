<?php

namespace App\Http\Controllers;

use App\Models\Veiculo;
use Illuminate\Http\Request;

class VeiculoController extends Controller
{
    public function index(Request $request)
    {
        $query = Veiculo::query();

        if ($request->filled('busca')) {
            $query->where('placa', 'like', '%' . $request->busca . '%')
                  ->orWhere('descricao', 'like', '%' . $request->busca . '%');
        }

        // Para listar todos, sem paginação (ou com, mas na view original era só $veiculos)
        $veiculos = $query->orderBy('placa')->paginate(50)->withQueryString();

        $sistemasRastreamento = Veiculo::whereNotNull('sistema_rastreamento')
                                    ->where('sistema_rastreamento', '!=', '')
                                    ->distinct()
                                    ->pluck('sistema_rastreamento');

        $descricoes = Veiculo::whereNotNull('descricao')
                                    ->where('descricao', '!=', '')
                                    ->distinct()
                                    ->pluck('descricao');

        return view('veiculos.index', compact('veiculos', 'sistemasRastreamento', 'descricoes'));
    }

    public function store(Request $request)
    {
        if ($request->has('placa')) {
            $request->merge([
                'placa' => strtoupper($request->placa)
            ]);
        }

        $request->validate([
            'placa' => ['required', 'string', 'size:7', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', 'unique:produtividade_veiculo,placa'],
            'descricao' => 'nullable|string|max:255',
            'sistema_rastreamento' => 'nullable|string|max:255',
        ]);

        Veiculo::create([
            'placa' => $request->placa,
            'descricao' => $request->descricao,
            'sistema_rastreamento' => $request->sistema_rastreamento,
            'status' => 'ativo',
        ]);

        return back()->with('success', 'Veículo criado com sucesso!');
    }

    public function update(Request $request, Veiculo $veiculo)
    {
        if ($request->has('placa')) {
            $request->merge([
                'placa' => strtoupper($request->placa)
            ]);
        }

        $request->validate([
            'placa' => ['required', 'string', 'size:7', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', 'unique:produtividade_veiculo,placa,' . $veiculo->id],
            'descricao' => 'nullable|string|max:255',
            'sistema_rastreamento' => 'nullable|string|max:255',
        ]);

        $veiculo->update([
            'placa' => $request->placa,
            'descricao' => $request->descricao,
            'sistema_rastreamento' => $request->sistema_rastreamento,
        ]);

        return back()->with('success', 'Veículo atualizado com sucesso!');
    }

    public function toggleStatus(Veiculo $veiculo)
    {
        $veiculo->status = $veiculo->status === 'ativo' ? 'inativo' : 'ativo';
        $veiculo->save();
        
        return back()->with('success', 'Status do veículo atualizado!');
    }
}
