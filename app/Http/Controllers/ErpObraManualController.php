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

        // Gestores e Coordenadores (Relacionamento N:N)
        $gestoresFiltros = [
            'gerente_implantacao' => 'implantacao',
            'gerente_manutencao' => 'manutencao',
            'coordenador_implantacao' => 'implantacao',
            'coordenador_manutencao' => 'manutencao',
        ];

        foreach ($gestoresFiltros as $campo => $pivotColumn) {
            $query->when($request->filled($campo), function ($q) use ($request, $campo, $pivotColumn) {
                return $q->whereHas('projetoOperacional', function ($q1) use ($request, $campo, $pivotColumn) {
                    // Garante a correspondência pela chave composta (Código + Unidade)
                    $q1->whereColumn('projetos_operacionais.unidade', 'erp_obras_manual.projeto_unidade')
                       ->whereHas('gestores', function ($q2) use ($request, $campo, $pivotColumn) {
                           $q2->where('colaborador_id', $request->query($campo))
                              ->where($pivotColumn, true);
                       });
                });
            });
        }

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
        $gerentes = Colaborador::ativos()->with(['notificacoes' => fn($q) => $q->where('lida', false)->latest()->limit(10)])->whereHas('user.roles', function($q) {
                                            $q->where('name', 'GERENCIAL');
                                        })
                                        ->orderBy('nome_completo')
                                        ->get();

        // Buscar Coordenadores
        $coordenadores = Colaborador::ativos()->with(['notificacoes' => fn($q) => $q->where('lida', false)->latest()->limit(10)])->whereHas('user.roles', function($q) {
                                            $q->where('name', 'COORDENADOR');
                                        })
                                        ->orderBy('nome_completo')
                                        ->get();

        // Buscar todos os setores ativos
        $setores = Setor::ativos()->orderBy('nome')->get();

        // Buscar Responsáveis Comerciais (Setores: COMERCIAL - 3, DIRETORIA - 5)
        $lideresComerciais = Colaborador::ativos()->with(['notificacoes' => fn($q) => $q->where('lida', false)->latest()->limit(10)])->whereIn('setor_id', [3, 5])
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
        $syncData = [];

        // Processa os de Implantação
        $implantacaoIds = array_merge($request->input('coordenadores_implantacao', []), $request->input('gerente_implantacao') ? [$request->input('gerente_implantacao')] : []);
        foreach ($implantacaoIds as $id) {
            $syncData[$id] = ['implantacao' => true, 'manutencao' => false];
        }

        // Processa os de Manutenção (mesclando caso a mesma pessoa faça os dois papéis)
        $manutencaoIds = array_merge($request->input('coordenadores_manutencao', []), $request->input('gerente_manutencao') ? [$request->input('gerente_manutencao')] : []);
        foreach ($manutencaoIds as $id) {
            if (isset($syncData[$id])) {
                $syncData[$id]['manutencao'] = true;
            } else {
                $syncData[$id] = ['implantacao' => false, 'manutencao' => true];
            }
        }

        if ($obra->projeto_codigo && $obra->projeto_unidade) {
            $projetoOp = \App\Models\ProjetoOperacional::where('codigo', $obra->projeto_codigo)
                ->where('unidade', $obra->projeto_unidade)
                ->first();
            if ($projetoOp) {
                $projetoOp->gestores()->sync($syncData);
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

    public function historico($id)
    {
        $obra = ErpObraManual::findOrFail($id);
        
        $historicos = \App\Models\ControleProjetoHistorico::with('editadoPor')
            ->where('projeto_original_id', $id)
            ->orderBy('numero_edicao', 'asc')
            ->get();

        $timeline = [];
        $snapshotAnterior = null;

        $dicionario = [
            'projeto_nome' => 'Nome do Projeto',
            'projeto_status' => 'Status do Projeto',
            'status_ativo' => 'Status (Ativo)',
            'valor_contrato' => 'Valor do Contrato',
            'cliente_codigo' => 'Código do Cliente',
            'projeto_codigo' => 'Código do Projeto',
            'projeto_unidade' => 'Unidade do Projeto',
            'cnpj' => 'CNPJ',
            'razao_social' => 'Razão Social',
            'endereco' => 'Endereço',
            'cidade' => 'Cidade',
            'tipo_categoria' => 'Categoria',
            'setor_id' => 'Setor Responsável',
            'projeto_etapa' => 'Etapa do Projeto',
            'cronograma_inicio' => 'Início do Cronograma',
            'cronograma_fim' => 'Fim do Cronograma',
            'target' => 'Data Target',
            'contrato_assinatura' => 'Assinatura do Contrato',
            'termo_entrega' => 'Termo de Entrega',
            'projeto_avanco' => 'Avanço do Projeto (%)',
            'pedagio' => 'Pedágio',
            'ausencia_cronograma' => 'Ausência de Cronograma',
            'ausencia_contrato' => 'Ausência de Contrato',
            'ausencia_termo' => 'Ausência de Termo',
            'valor_venda' => 'Valor de Venda',
            'valor_monitoramento' => 'Valor de Monitoramento',
            'valor_licenca' => 'Valor de Licença',
            'valor_manutencao' => 'Valor de Manutenção',
            'valor_locacao' => 'Valor de Locação',
            'comentarios' => 'Comentários',
            'lider_comercial_id' => 'Responsável Comercial',
            'gestores' => 'Gestores e Responsáveis',
        ];

        // Cache para IDs de Setor e Colaborador para não fazer query em loop
        $setores = \App\Models\Setor::pluck('nome', 'id')->toArray();
        $colaboradores = \App\Models\Colaborador::pluck('nome_completo', 'id')->toArray();

        foreach ($historicos as $hist) {
            $snapshotAtual = is_array($hist->dados_snapshot) ? $hist->dados_snapshot : json_decode($hist->dados_snapshot, true);
            $mudancas = [];

            if ($snapshotAnterior === null) {
                // É a criação
                $snapshotAnterior = $snapshotAtual;
                $timeline[] = [
                    'edicao' => $hist->numero_edicao,
                    'data' => $hist->data_edicao ? $hist->data_edicao->format('d/m/Y H:i:s') : 'Desconhecida',
                    'autor' => $hist->editadoPor ? $hist->editadoPor->name : 'Sistema',
                    'mudancas' => [[
                        'campo_formatado' => 'Criação',
                        'de' => '',
                        'para' => 'Registro Inicial Criado'
                    ]]
                ];
                continue; 
            }

            foreach ($snapshotAtual as $campo => $valorAtualRaw) {
                if (in_array($campo, ['updated_at', 'created_at', 'id', 'gestores_ids', 'lider_comercial'])) {
                    continue; // Ignorar timestamps, ids e helpers
                }

                $valorAntigoRaw = $snapshotAnterior[$campo] ?? null;

                // Tratamento especial para Gestores (array de objetos)
                if ($campo === 'gestores') {
                    $atualGestores = collect($valorAtualRaw)->map(function($g) {
                        return $g['nome_completo'] ?? '';
                    })->filter()->implode('\n');
                    
                    $antigoGestores = collect($valorAntigoRaw)->map(function($g) {
                        return ($g['nome_completo'] ?? '');
                    })->filter()->implode('\n');
                    
                    if ($atualGestores !== $antigoGestores) {
                        $mudancas[] = [
                            'campo_raw' => $campo,
                            'campo_formatado' => $dicionario[$campo] ?? 'Gestores',
                            'de' => $antigoGestores ?: 'Nenhum',
                            'para' => $atualGestores ?: 'Nenhum'
                        ];
                    }
                    continue;
                }

                // Normalização para string (para floats, nulls, booleanos, etc)
                $valorAtual = is_array($valorAtualRaw) ? json_encode($valorAtualRaw) : strval($valorAtualRaw);
                $valorAntigo = is_array($valorAntigoRaw) ? json_encode($valorAntigoRaw) : strval($valorAntigoRaw);
                
                // Formatar booleanos
                if (is_bool($valorAtualRaw) || in_array($campo, ['status_ativo', 'pedagio', 'ausencia_cronograma', 'ausencia_contrato', 'ausencia_termo'])) {
                    $valorAtual = $valorAtualRaw ? 'Sim' : 'Não';
                    $valorAntigo = $valorAntigoRaw ? 'Sim' : 'Não';
                }

                if ($valorAtual !== $valorAntigo) {
                    
                    // Tradução de FKs (Join manual)
                    if ($campo === 'setor_id') {
                        $valorAntigo = $setores[$valorAntigoRaw] ?? $valorAntigo;
                        $valorAtual = $setores[$valorAtualRaw] ?? $valorAtual;
                    } elseif ($campo === 'lider_comercial_id') {
                        $valorAntigo = $colaboradores[$valorAntigoRaw] ?? $valorAntigo;
                        $valorAtual = $colaboradores[$valorAtualRaw] ?? $valorAtual;
                    }

                    $mudancas[] = [
                        'campo_raw' => $campo,
                        'campo_formatado' => $dicionario[$campo] ?? ucfirst(str_replace('_', ' ', $campo)),
                        'de' => $valorAntigo,
                        'para' => $valorAtual
                    ];
                }
            }

            if (!empty($mudancas)) {
                $timeline[] = [
                    'edicao' => $hist->numero_edicao,
                    'data' => $hist->data_edicao ? $hist->data_edicao->format('d/m/Y H:i:s') : 'Desconhecida',
                    'autor' => $hist->editadoPor ? $hist->editadoPor->name : 'Sistema',
                    'mudancas' => $mudancas
                ];
            }

            $snapshotAnterior = $snapshotAtual;
        }

        $timeline = array_reverse($timeline);

        return view('erp_obras_manual.partials.historico_modal', compact('obra', 'timeline'));
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

        $obraComNome = $obrasDoCliente->firstWhere('projeto_nome', '!=', null);
        
        if ($obraComNome && !empty($obraComNome->projeto_nome)) {
            return response()->json([
                'acao' => 'travar',
                'nome' => $obraComNome->projeto_nome,
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
        $obra = ErpObraManual::with(['setor', 'liderComercial', 'projetoOperacional.gestores.user'])->findOrFail($id);
        return view('erp_obras_manual.show', compact('obra'));
    }
}
