<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Models\Colaborador;
use App\Models\CodigoCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjetoController extends Controller
{
    public function index(Request $request)
    {
        $query = Projeto::with('gestores')->orderBy('nome');

        $busca = $request->query('busca');
        if ($busca) {
            $query->where(function($q) use ($busca) {
                $q->where('nome', 'ilike', '%' . $busca . '%')
                  ->orWhere('codigo', 'ilike', '%' . $busca . '%');
            });
        }

        $projetos = $query->paginate(15);
        $projetos->appends($request->all());

        // Buscar apenas colaboradores com perfil de gestão via Spatie
        $possiveisGestores = Colaborador::ativos()->whereHas('user.roles', function($q) {
                                            $q->whereIn('name', ['COORDENADOR', 'ADMIN', 'GERENCIAL']);
                                        })
                                        ->orderBy('nome_completo')
                                        ->get();

        $todosProjetos = Projeto::select('nome', 'codigo')->orderBy('nome')->get();

        return view('projetos.index', compact('projetos', 'possiveisGestores', 'todosProjetos'));
    }

    public function store(Request $request)
    {
        abort(403, 'A criação manual de obras foi desativada. Sincronize com o ERP.');
    }

    public function update(Request $request, $id)
    {
        $projeto = Projeto::findOrFail($id);
        $gestoresIds = $request->input('gestores_ids', []);

        DB::beginTransaction();
        try {
            // Sincroniza gestores com a obra
            $projeto->gestores()->sync($gestoresIds);

            // Automação: Sincroniza o acesso ao Cliente pai da obra
            if ($projeto->codigo_cliente_id) {
                foreach ($gestoresIds as $colaboradorId) {
                    $colaborador = Colaborador::find($colaboradorId);
                    if ($colaborador) {
                        $colaborador->clientesGerenciados()->syncWithoutDetaching([$projeto->codigo_cliente_id]);
                    }
                }
            }
            DB::commit();
            return redirect()->back()->with('success', 'Gestores vinculados com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao vincular gestores: ' . $e->getMessage());
        }
    }

    public function sincronizarErp()
    {
        $dadosErp = \Illuminate\Support\Facades\DB::table('erp_obras_api')->get();

        DB::beginTransaction();
        try {
            foreach ($dadosErp as $linha) {
                $cliente = CodigoCliente::updateOrCreate(
                    ['codigo' => $linha->cliente_codigo],
                    ['nome' => $linha->projeto_nome, 'ativo' => $linha->status_ativo]
                );

                Projeto::updateOrCreate(
                    ['codigo' => $linha->projeto_codigo],
                    [
                        'nome' => $linha->projeto_nome,
                        'ativo' => $linha->status_ativo,
                        'codigo_cliente_id' => $cliente->id
                    ]
                );
            }
            DB::commit();
            return redirect()->back()->with('success', 'Sincronização com o ERP concluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Falha na sincronização: ' . $e->getMessage());
        }
    }
}
