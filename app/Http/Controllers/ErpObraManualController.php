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

        $search = $request->query('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('projeto_codigo', 'ilike', '%' . $search . '%')
                  ->orWhere('projeto_nome', 'ilike', '%' . $search . '%')
                  ->orWhere('projeto_unidade', 'ilike', '%' . $search . '%')
                  ->orWhere('cnpj', 'ilike', '%' . $search . '%');
            });
        }

        // Filtros de Texto (LIKE)
        $textFields = ['cliente_codigo', 'projeto_codigo', 'projeto_nome', 'projeto_unidade', 'cidade', 'cnpj'];
        foreach ($textFields as $field) {
            $query->when($request->filled($field), function ($q) use ($request, $field) {
                return $q->where($field, 'ilike', '%' . $request->query($field) . '%');
            });
        }

        // Correspondência Exata / Selects
        $exactFields = ['tipo_categoria', 'setor_id', 'projeto_etapa', 'projeto_status', 'lider_comercial'];
        foreach ($exactFields as $field) {
            $query->when($request->filled($field), function ($q) use ($request, $field) {
                return $q->where($field, $request->query($field));
            });
        }

        // Booleanos / Checkboxes (Sim/Não)
        $booleanFields = ['status_ativo', 'pedagio', 'ausencia_cronograma', 'ausencia_contrato', 'ausencia_termo'];
        foreach ($booleanFields as $field) {
            $query->when($request->filled($field), function ($q) use ($request, $field) {
                return $q->where($field, $request->query($field) == '1' ? 1 : 0);
            });
        }

        // Datas
        $query->when($request->filled('target'), function ($q) use ($request) {
            return $q->where('target', $request->query('target'));
        });

        // Coordenadores (Relacionamento N:N)
        $query->when($request->filled('coordenador_implantacao'), function ($q) use ($request) {
            return $q->whereHas('projetoOperacional.gestores', function ($q2) use ($request) {
                $q2->where('colaborador_id', $request->query('coordenador_implantacao'));
            });
        });
        
        $query->when($request->filled('coordenador_manutencao'), function ($q) use ($request) {
            return $q->whereHas('projetoOperacional.gestores', function ($q2) use ($request) {
                $q2->where('colaborador_id', $request->query('coordenador_manutencao'));
            });
        });

        // 1. Paginação carregando apenas relações simples
        $obras = $query->with(['setor', 'liderComercial'])->paginate(15);
        $obras->appends($request->all());

        // 2. Custom Eager Loading de Chave Composta (Código + Unidade)
        $codigos = $obras->pluck('projeto_codigo')->filter()->unique()->toArray();
        $unidades = $obras->pluck('projeto_unidade')->filter()->unique()->toArray();

        if (!empty($codigos)) {
            // Busca todos os Projetos Operacionais possíveis na página atual (e seus gestores)
            $projetosOps = \App\Models\ProjetoOperacional::with('gestores')
                ->whereIn('codigo', $codigos)
                ->whereIn('unidade', $unidades)
                ->get();

            // 3. Hidrata manualmente a relação na Collection
            foreach ($obras as $obra) {
                $projetoCorreto = $projetosOps->first(function($p) use ($obra) {
                    return $p->codigo === $obra->projeto_codigo && $p->unidade === $obra->projeto_unidade;
                });
                // Injeta a relação correta na memória.
                // Isso faz com que $obra->projetoOperacional retorne o objeto exato.
                $obra->setRelation('projetoOperacional', $projetoCorreto);
            }
        }

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

        if ($obra->projeto_codigo && $obra->projeto_unidade) {
            $projetoOp = \App\Models\ProjetoOperacional::where('codigo', $obra->projeto_codigo)
                ->where('unidade', $obra->projeto_unidade)
                ->first();
            if ($projetoOp) {
                $projetoOp->gestores()->sync($coordenadoresCombinados);
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

    public function verificarCliente(Request $request)
    {
        $clienteCodigo = $request->input('cliente_codigo');
        $cnpj = $request->input('cnpj');
        
        $razaoSocial = null;
        $cnpjLimpo = null;
        
        // 1. Independentemente do código do cliente, verifica se o CNPJ existe para puxar a Razão Social (que é imutável)
        if ($cnpj) {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
            if (!empty($cnpjLimpo)) {
                $obraComCnpj = ErpObraManual::where('cnpj', $cnpjLimpo)
                                            ->whereNotNull('razao_social')
                                            ->where('razao_social', '!=', '')
                                            ->first();
                                            
                if ($obraComCnpj) {
                    $razaoSocial = $obraComCnpj->razao_social;
                }
            }
        }

        if (!$clienteCodigo) {
            return response()->json([
                'acao' => 'livre', 
                'razao_social' => $razaoSocial
            ]);
        }

        $obrasDoCliente = ErpObraManual::where('cliente_codigo', $clienteCodigo)->get();

        if ($obrasDoCliente->isEmpty()) {
            return response()->json([
                'acao' => 'livre',
                'razao_social' => $razaoSocial
            ]);
        }

        if ($cnpj && !empty($cnpjLimpo)) {
            $obraExata = $obrasDoCliente->firstWhere('cnpj', $cnpjLimpo);
            
            if ($obraExata && !empty($obraExata->projeto_nome)) {
                return response()->json([
                    'acao' => 'travar',
                    'nome' => $obraExata->projeto_nome,
                    'razao_social' => $razaoSocial ?? $obraExata->razao_social
                ]);
            }
        }

        $nomesUnicos = $obrasDoCliente->pluck('projeto_nome')
                                      ->filter()
                                      ->unique()
                                      ->values()
                                      ->toArray();

        if (count($nomesUnicos) > 0) {
            return response()->json([
                'acao' => 'sugerir',
                'sugestoes' => $nomesUnicos,
                'razao_social' => $razaoSocial
            ]);
        }

        return response()->json([
            'acao' => 'livre',
            'razao_social' => $razaoSocial
        ]);
    }

    public function show($id)
    {
        $obra = ErpObraManual::findOrFail($id);
        return view('erp_obras_manual.show', compact('obra'));
    }
}
