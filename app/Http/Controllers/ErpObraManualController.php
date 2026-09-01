<?php

namespace App\Http\Controllers;

use App\Models\ErpObraManual;
use App\Models\Colaborador;
use App\Models\Setor;
use App\Http\Requests\ErpObraManualRequest;
use Illuminate\Http\Request;

class ErpObraManualController extends Controller
{
    public function index(Request $request)
    {
        $query = ErpObraManual::orderBy('projeto_nome');

        $busca = $request->query('busca');
        if ($busca) {
            $query->where(function($q) use ($busca) {
                $q->where('projeto_nome', 'ilike', '%' . $busca . '%')
                  ->orWhere('projeto_codigo', 'ilike', '%' . $busca . '%')
                  ->orWhere('cliente_razao_social', 'ilike', '%' . $busca . '%')
                  ->orWhere('cliente_codigo', 'ilike', '%' . $busca . '%');
            });
        }

        $obras = $query->with(['setor', 'liderComercial', 'gerenteImplantacao', 'gerenteManutencao', 'coordenadoresProjeto'])->paginate(15);
        $obras->appends($request->all());

        // Buscar Gerentes
        $gerentes = Colaborador::ativos()->whereHas('user.roles', function($q) {
                                            $q->where('name', 'GERENCIAL');
                                        })
                                        ->orderBy('nome_completo')
                                        ->get();

        // Buscar Coordenadores
        $coordenadores = Colaborador::ativos()->whereHas('user.roles', function($q) {
                                            $q->where('name', 'COORDENADOR');
                                        })
                                        ->orderBy('nome_completo')
                                        ->get();

        // Buscar todos os setores ativos
        $setores = Setor::ativos()->orderBy('nome')->get();

        // Buscar Responsáveis Comerciais (Setores: COMERCIAL - 3, DIRETORIA - 5)
        $lideresComerciais = Colaborador::ativos()->whereIn('setor_id', [3, 5])
                                        ->orderBy('nome_completo')
                                        ->get();

        // Buscar Etapas (Setores: IDs 9, 11, 13, 14)
        $etapas = Setor::whereIn('id', [9, 11, 13, 14])->get();

        $todasObras = ErpObraManual::select('projeto_nome', 'projeto_codigo')->orderBy('projeto_nome')->get();

        return view('erp_obras_manual.index', compact('obras', 'gerentes', 'coordenadores', 'setores', 'lideresComerciais', 'todasObras', 'etapas'));
    }

    public function store(ErpObraManualRequest $request)
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $obra = ErpObraManual::create($request->validated());
            $this->sincronizarGestores($obra, $request);
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'Obra criada com sucesso!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao criar obra: ' . $e->getMessage())->withInput();
        }
    }

    public function update(ErpObraManualRequest $request, $id)
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $obra = ErpObraManual::findOrFail($id);
            $obra->update($request->validated());
            $this->sincronizarGestores($obra, $request);
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'Obra atualizada com sucesso!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Erro ao atualizar obra: ' . $e->getMessage())->withInput();
        }
    }

    private function sincronizarGestores(ErpObraManual $obra, ErpObraManualRequest $request)
    {
        $coordImp = $request->input('coordenadores_implantacao', []);
        $coordMan = $request->input('coordenadores_manutencao', []);

        $coordenadoresCombinados = array_unique(array_filter(array_merge($coordImp, $coordMan)));

        $obra->coordenadoresProjeto()->sync($coordenadoresCombinados);

        if ($obra->cliente_codigo) {
            $cliente = \App\Models\CodigoCliente::where('codigo', $obra->cliente_codigo)->first();
            
            if ($cliente) {
                foreach ($coordenadoresCombinados as $colaboradorId) {
                    $colaborador = \App\Models\Colaborador::find($colaboradorId);
                    if ($colaborador) {
                        $colaborador->clientesGerenciados()->syncWithoutDetaching([$cliente->id]);
                    }
                }
            }
        }
    }

    public function destroy($id)
    {
        try {
            $obra = ErpObraManual::findOrFail($id);
            $obra->delete();
            return redirect()->back()->with('success', 'Obra excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao excluir obra: ' . $e->getMessage());
        }
    }

    public function sugestoesNome(Request $request)
    {
        $clienteCodigo = $request->query('cliente_codigo');
        $cnpj = $request->query('cnpj');

        if (!$clienteCodigo) {
            return response()->json(['match_exato' => false, 'nomes' => []]);
        }

        // Busca registros do mesmo cliente
        $obrasDoCliente = ErpObraManual::where('cliente_codigo', $clienteCodigo)->get();

        if ($obrasDoCliente->isEmpty()) {
            return response()->json(['match_exato' => false, 'nomes' => []]);
        }

        // Verifica match exato (Cliente + CNPJ)
        $matchExato = false;
        $nomeExato = null;
        $razaoSocialExata = null;

        if ($cnpj) {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
            
            $obraExata = $obrasDoCliente->first(function($obra) use ($cnpjLimpo) {
                $dbCnpj = preg_replace('/[^0-9]/', '', (string)$obra->cnpj);
                return $dbCnpj === $cnpjLimpo;
            });
            
            if ($obraExata) {
                $matchExato = true;
                if (!empty($obraExata->projeto_nome)) {
                    $nomeExato = $obraExata->projeto_nome;
                }
                if (!empty($obraExata->razao_social)) {
                    $razaoSocialExata = $obraExata->razao_social;
                }
            }
        }

        // Pegar todos os nomes únicos do cliente
        $nomesUnicos = $obrasDoCliente->pluck('projeto_nome')
                                      ->filter()
                                      ->unique()
                                      ->values()
                                      ->toArray();

        return response()->json([
            'match_exato' => $matchExato,
            'nome_exato' => $nomeExato,
            'razao_social_exata' => $razaoSocialExata,
            'nomes' => $nomesUnicos
        ]);
    }

    public function show($id)
    {
        $obra = ErpObraManual::findOrFail($id);
        return view('erp_obras_manual.show', compact('obra'));
    }
}
