<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;

class ColaboradorController extends Controller
{
    public function index(Request $request)
    {
        $query = Colaborador::with(['setorRelacionamento', 'setoresVinculados:id', 'user.roles']);

        if ($request->filled('nome')) {
            $query->where('nome_completo', 'ilike', '%' . $request->nome . '%');
        }
        if ($request->filled('cargo')) {
            $query->where('cargo', $request->cargo);
        }
        if ($request->filled('setor_id')) {
            $query->where('setor_id', $request->setor_id);
        }
        if ($request->filled('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->role($request->role);
            });
        }
        if ($request->filled('cidade_trabalho')) {
            $query->where('cidade_trabalho', $request->cidade_trabalho);
        }
        if ($request->filled('status')) {
            if ($request->status === 'ativo') {
                $query->whereNull('data_demissao');
            } elseif ($request->status === 'inativo') {
                $query->whereNotNull('data_demissao');
            }
        }

        // 1º Ordena por Status (Ativos primeiro)
        $query->orderByRaw("CASE WHEN data_demissao IS NULL THEN 0 ELSE 1 END ASC");

        // 2º Ordena alfabeticamente
        $query->orderBy('nome_completo', 'asc');

        $colaboradores = $query->paginate(25)->withQueryString();
        
        $cargos = Colaborador::whereNotNull('cargo')->distinct()->pluck('cargo');
        $setores = \App\Models\Setor::orderBy('nome')->get();
        $roles = \Spatie\Permission\Models\Role::all();
        $cidades = Colaborador::select('cidade_moradia')
            ->union(Colaborador::select('cidade_trabalho'))
            ->whereNotNull('cidade_moradia')
            ->distinct()
            ->pluck('cidade_moradia');
        $cidades_trabalho = Colaborador::whereNotNull('cidade_trabalho')->distinct()->pluck('cidade_trabalho');

        $usuariosPendentes = \App\Models\User::whereNull('produtividade_colaborador_id')
                                ->where('ignorado_erp', false)
                                ->get();
                                
        $usuariosIgnorados = \App\Models\User::whereNull('produtividade_colaborador_id')
                                ->where('ignorado_erp', true)
                                ->get();

        return view('colaboradores.index', compact('colaboradores', 'cargos', 'setores', 'cidades', 'cidades_trabalho', 'roles', 'usuariosPendentes', 'usuariosIgnorados'));
    }

    public function syncErp(\App\Services\ErpIntegrationService $erpService)
    {
        try {
            $erpService->syncUsuarios();
            return redirect()->back()->with('success', 'Sincronização de usuários concluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao sincronizar: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nome_completo'       => 'sometimes|nullable|string|max:255',
            'role'                => ['sometimes', 'nullable', 'string', 'exists:roles,name'], // Fase de Transição: substituiu nivel_acesso
            'telefone'            => 'sometimes|nullable|string|max:20',
            'cargo'               => 'sometimes|nullable|string|max:255',
            'setor_id'            => 'sometimes|nullable|exists:setores,id',
            'setores_vinculados'  => 'sometimes|nullable|array',
            'setores_vinculados.*'=> 'exists:setores,id',
            'cidade_moradia'      => 'sometimes|nullable|string|max:255',
            'cidade_trabalho'     => 'sometimes|nullable|string|max:255',
            'uf_moradia'          => 'sometimes|nullable|string|max:2',
            'uf_trabalho'         => 'sometimes|nullable|string|max:2',
            'data_demissao'       => 'sometimes|nullable|date',
            'data_vigencia'       => 'required|date'
        ]);

        $colaborador = Colaborador::findOrFail($id);
        
        $dados = $validated;
        
        // Remove campos processados separadamente para não cair no fill()
        $dataVigencia = $dados['data_vigencia'];
        unset($dados['data_vigencia']);

        $setoresVinculados = $request->input('setores_vinculados', []);
        unset($dados['setores_vinculados']);

        // Remove 'role' do array de dados do colaborador (não é coluna direta nesta fase)
        $roleParaSincronizar = $dados['role'] ?? null;
        unset($dados['role']);

        // Concatena a UF na Cidade de Moradia
        if (!empty($dados['cidade_moradia']) && !empty($dados['uf_moradia'])) {
            $dados['cidade_moradia'] = $dados['cidade_moradia'] . ' - ' . $dados['uf_moradia'];
        }

        // Concatena a UF na Cidade de Trabalho
        if (!empty($dados['cidade_trabalho']) && !empty($dados['uf_trabalho'])) {
            $dados['cidade_trabalho'] = $dados['cidade_trabalho'] . ' - ' . $dados['uf_trabalho'];
        }

        // Remove os campos auxiliares de UF
        unset($dados['uf_moradia'], $dados['uf_trabalho'], $dados['uf']);

        // Sanitização global: uppercase e ASCII em todos os textos, exceto datas
        foreach ($dados as $key => $value) {
            if (is_string($value) && !in_array($key, ['data_demissao'])) {
                $dados[$key] = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($value));
            }
        }

        $colaborador->fill($dados);
        $colaborador->dataVigenciaVirtual = $dataVigencia;
        $colaborador->save();

        // Sincroniza os setores vinculados
        $colaborador->setoresVinculados()->sync($setoresVinculados);

        // --- FASE DE TRANSIÇÃO: Sincronização Spatie + Espelhamento Temporário ---
        // O campo nivel_acesso ainda existe para compatibilidade durante a migração de 30 dias.
        if ($colaborador->user && $roleParaSincronizar) {
            // 1. Atualiza a role autoritativa no Spatie (fonte de verdade)
            $colaborador->user->syncRoles([$roleParaSincronizar]);

            // 2. Espelha no campo legado sem disparar eventos Eloquent (evita loop com Observer)
            $colaborador->updateQuietly(['nivel_acesso' => $roleParaSincronizar]);
        }
        // --- FIM DA FASE DE TRANSIÇÃO ---

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Colaborador atualizado com sucesso.']);
        }

        return back()->with('success', 'Ficha atualizada com sucesso!');
    }

    public function historico($id)
    {
        $colaborador = Colaborador::with(['historicos' => function($query) {
            $query->orderBy('created_at', 'desc')->with('usuarioAlteracao:id,name');
        }])->findOrFail($id);

        $historicosFormatados = $colaborador->historicos->map(function ($hist) {
            $camposAlterados = is_array($hist->campos_alterados) ? $hist->campos_alterados : json_decode($hist->campos_alterados, true) ?? [];
            $dadosAnteriores = is_array($hist->dados_anteriores) ? $hist->dados_anteriores : json_decode($hist->dados_anteriores, true) ?? [];

            if (isset($camposAlterados['setor_id'])) {
                $novoId = $camposAlterados['setor_id'];
                unset($camposAlterados['setor_id']);
                $camposAlterados['Setor'] = $novoId ? \App\Models\Setor::find($novoId)->nome ?? $novoId : '(vazio)';
                
                if (isset($dadosAnteriores['setor_id'])) {
                    $antigoId = $dadosAnteriores['setor_id'];
                    unset($dadosAnteriores['setor_id']);
                    $dadosAnteriores['Setor'] = $antigoId ? \App\Models\Setor::find($antigoId)->nome ?? $antigoId : '(vazio)';
                }
            }

            if (isset($camposAlterados['uf'])) {
                $novoValor = \Illuminate\Support\Str::upper($camposAlterados['uf']);
                unset($camposAlterados['uf']);
                $camposAlterados['UF'] = $novoValor;
                
                if (isset($dadosAnteriores['uf'])) {
                    unset($dadosAnteriores['uf']);
                    $dadosAnteriores['UF'] = \Illuminate\Support\Str::upper($dadosAnteriores['uf']);
                }
            }

            $hist->campos_alterados = $camposAlterados;
            $hist->dados_anteriores = $dadosAnteriores;
            
            return $hist;
        });

        return response()->json($historicosFormatados);
    }

    public function buscarCidades(Request $request)
    {
        try {
            $busca = $request->query('q');

            // Puxa todos os municípios do IBGE e guarda em Cache por 24 horas
            $municipios = \Illuminate\Support\Facades\Cache::remember('ibge_municipios', 86400, function () {
                $response = \Illuminate\Support\Facades\Http::timeout(15)
                    ->get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios');
                
                if ($response->successful()) {
                    return collect($response->json())->map(function ($item) {
                        return [
                            'nome' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($item['nome'])),
                            // A estrutura do IBGE aninha a UF dentro de microrregião > mesorregião
                            'uf' => $item['microrregiao']['mesorregiao']['UF']['sigla'] ?? ''
                        ];
                    })->toArray();
                }
                return [];
            });

            if (empty($municipios)) {
                return response()->json(['error' => 'Falha ao carregar base do IBGE'], 500);
            }

            // Filtra os resultados ignorando acentos e maiúsculas (ex: "sao paulo" encontra "São Paulo")
            if (!empty($busca)) {
                $buscaSlug = \Illuminate\Support\Str::slug($busca);
                $municipios = array_filter($municipios, function($m) use ($buscaSlug) {
                    return \Illuminate\Support\Str::contains(\Illuminate\Support\Str::slug($m['nome']), $buscaSlug);
                });
            }

            // Retorna apenas os 15 primeiros resultados formatados
            return response()->json(array_values(array_slice($municipios, 0, 15)));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buscarNomesAoVivo(Request $request)
    {
        $busca = $request->query('q');
        if (empty($busca)) return response()->json([]);
        
        $colaboradores = Colaborador::where('nome_completo', 'ilike', "%{$busca}%")
                            ->limit(10)
                            ->get(['id', 'nome_completo', 'cargo']);
                            
        return response()->json($colaboradores);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_completo'       => 'required|string|max:255',
            'role'                => ['required', 'string', 'exists:roles,name'], // Fase de Transição: substituiu nivel_acesso
            'id_colaborador'      => 'required|string|max:255', // Removido unique para permitir o updateOrCreate do ERP
            'telefone'            => 'nullable|string|max:20',
            'cargo'               => 'required|string|max:255',
            'setor_id'            => 'required|exists:setores,id',
            'setores_vinculados'  => 'nullable|array',
            'setores_vinculados.*'=> 'exists:setores,id',
            'cidade_moradia'      => 'nullable|string|max:255',
            'cidade_trabalho'     => 'nullable|string|max:255',
            'data_admissao'       => 'required|date',
            'uf_moradia'          => 'nullable|string|max:2',
            'uf_trabalho'         => 'nullable|string|max:2',
        ]);

        $dados = $validated;

        // Remove campos processados separadamente para não cair no fill()
        $setoresVinculados = $request->input('setores_vinculados', []);
        unset($dados['setores_vinculados']);

        $roleParaSincronizar = $dados['role'];
        unset($dados['role']);

        // Concatena a UF na Cidade de Moradia
        if (!empty($dados['cidade_moradia']) && !empty($dados['uf_moradia'])) {
            $dados['cidade_moradia'] = $dados['cidade_moradia'] . ' - ' . $dados['uf_moradia'];
        }

        // Concatena a UF na Cidade de Trabalho
        if (!empty($dados['cidade_trabalho']) && !empty($dados['uf_trabalho'])) {
            $dados['cidade_trabalho'] = $dados['cidade_trabalho'] . ' - ' . $dados['uf_trabalho'];
        }

        // Remove os campos auxiliares
        unset($dados['uf_moradia'], $dados['uf_trabalho'], $dados['uf']);

        foreach ($dados as $key => $value) {
            if (is_string($value) && !in_array($key, ['data_admissao', 'data_demissao', 'data_vigencia'])) {
                $dados[$key] = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($value));
            }
        }

        // --- FASE DE TRANSIÇÃO: Grava nivel_acesso legado para compatibilidade durante a migração ---
        // Será removido após a Fase 6 (deprecação do campo).
        $dados['nivel_acesso'] = $roleParaSincronizar;
        // --- FIM DA FASE DE TRANSIÇÃO ---

        $colaborador = Colaborador::updateOrCreate(
            ['id_colaborador' => $dados['id_colaborador']],
            $dados
        );

        $colaborador->setoresVinculados()->sync($setoresVinculados);

        // --- FASE DE TRANSIÇÃO: Sincronização Spatie ---
        // Colaborador recém-criado geralmente não tem User ainda (criado depois no SSO).
        // Se já tiver, sincroniza imediatamente.
        if ($colaborador->user) {
            $colaborador->user->syncRoles([$roleParaSincronizar]);
        }
        // --- FIM DA FASE DE TRANSIÇÃO ---

        $user_id = $request->input('user_id');
        if ($user_id) {
            $user = \App\Models\User::find($user_id);
            if ($user) {
                $user->update(['produtividade_colaborador_id' => $colaborador->id]);
                $user->syncRoles([$roleParaSincronizar]);
            }
        }

        return back()->with('success', 'Colaborador cadastrado com sucesso!');
    }

    public function ignorarUserErp($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->update(['ignorado_erp' => true]);
        return redirect()->back()->with('success', 'Usuário ignorado com sucesso.');
    }

    public function designorarUserErp($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->update(['ignorado_erp' => false]);
        return redirect()->back()->with('success', 'Usuário retornado para a lista de pendentes com sucesso.');
    }
}
