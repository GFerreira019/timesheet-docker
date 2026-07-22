<?php
namespace App\Http\Controllers;
use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\ApontamentoHistorico;
use App\Models\CentroCusto;
use App\Models\CodigoCliente;
use App\Models\Colaborador;
use App\Models\Projeto;
use App\Models\Veiculo;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
/**
 * AprovacaoController
 *
 * Equivalente às views Django:
 *   aprovacao_dashboard_view()   → dashboard()
 *   analise_apontamento_view()   → analise()
 *   processar_aprovacao_view()   → processar()
 *
 * Requer role GESTOR ou is_superuser.
 */
class AprovacaoController extends Controller
{
    /**
     * Lista pendências para o Gerente/Owner aprovar ou rejeitar.
     * Equivalente ao aprovacao_dashboard_view() do Django.
     *
     * GET /aprovacoes
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        $user          = auth()->user();
        $ehOwner       = AcessoHelper::isOwner($user);
        $colaborador   = $user->colaborador;
        $colaboradorId = $colaborador->id ?? null;
        $nivelAcesso   = $colaborador ? strtoupper($colaborador->nivel_acesso) : 'OPERACIONAL';
        if ($ehOwner) {
            $nivelAcesso = 'ADMIN';
        }
        // 1. Query Base blindada
        $queryBase = Apontamento::with(['colaborador', 'projeto', 'centroCusto', 'auxiliar', 'auxiliaresExtras']);
        if ($nivelAcesso !== 'ADMIN') {
            // Regras normais (Gestor/Gerencial)
            $queryBase->visibilidadePermitida($user)
                ->when($colaboradorId, function($query) use ($colaboradorId) {
                    $query->where('colaborador_id', '!=', $colaboradorId);
                });
        } else {
            // ADMIN: sem restrições, mas com aplicação de filtros dinâmicos
            $queryBase->when($request->data_inicio, function($q, $v) {
                $q->whereDate('data_apontamento', '>=', $v);
            })->when($request->data_fim, function($q, $v) {
                $q->whereDate('data_apontamento', '<=', $v);
            })->when($request->colaborador_id, function($q, $v) {
                $q->where('colaborador_id', $v);
            })->when($request->projeto_id, function($q, $v) {
                $q->where('projeto_id', $v);
            })->when($request->setor_id, function($q, $v) {
                $q->whereHas('colaborador', function($q2) use ($v) {
                    $q2->where('setor_id', $v);
                });
            });
        }
        // 2. Contagens para os Cards (Usando clone para não afetar a query base)
        $totalPendentes = (clone $queryBase)->where('status_aprovacao', 'EM_ANALISE')->count();
        $totalAprovados = (clone $queryBase)->where('status_aprovacao', 'APROVADO')->count();
        $totalRecusados = (clone $queryBase)->whereIn('status_aprovacao', ['REJEITADO', 'RECUSADO'])->count();
        // 3. Aplicação de Filtros da Tela (Ex: O utilizador clicou no card de Aprovados ou status pelo form)
        $statusFiltro = $request->input('status', 'EM_ANALISE'); // Por padrão, mostra os pendentes
        
        if ($statusFiltro) {
            // Se o filtro for REJEITADO, englobamos RECUSADO também (caso haja legado)
            if ($statusFiltro === 'REJEITADO') {
                $queryBase->whereIn('status_aprovacao', ['REJEITADO', 'RECUSADO']);
            } elseif ($statusFiltro !== 'TODOS') {
                $queryBase->where('status_aprovacao', $statusFiltro);
            }
        }
        // 4. Busca final dos registos para a tabela (paginação)
        $pendentes = $queryBase->orderByDesc('data_apontamento')
            ->orderBy('colaborador_id')
            ->orderByDesc('hora_termino')
            ->paginate(15);
        // Dados para os menus dos filtros (somente se for ADMIN para otimizar query)
        $colaboradores = [];
        $projetos = [];
        $setores = [];
        if ($nivelAcesso === 'ADMIN') {
            $colaboradores = \App\Models\Colaborador::ativos()->orderBy('nome_completo')->get();
            $projetos = \App\Models\Projeto::ativos()->orderBy('codigo')->get();
            $setores = \App\Models\Setor::ativos()->orderBy('nome')->get();
        }
        return view('aprovacoes.dashboard', [
            'titulo'         => 'Central de Aprovações',
            'pendentes'      => $pendentes,
            'totalPendentes' => $totalPendentes,
            'totalAprovados' => $totalAprovados,
            'totalRecusados' => $totalRecusados,
            'statusFiltro'   => $statusFiltro,
            'is_owner'       => $ehOwner,
            'nivelAcesso'    => $nivelAcesso,
            'colaboradores'  => $colaboradores,
            'projetos'       => $projetos,
            'setores'        => $setores,
        ]);
    }
    /**
     * Tela de análise detalhada com Diff visual das alterações.
     * Equivalente ao analise_apontamento_view() do Django (12 campos de diff).
     *
     * GET /aprovacoes/{id}/analisar
     */
    public function analise(int $id): View
    {
        $apontamento = Apontamento::with([
            'colaborador', 'projeto', 'codigoCliente',
            'centroCusto', 'veiculo', 'auxiliar', 'auxiliaresExtras'
        ])->findOrFail($id);
        // Pega o histórico mais recente (equivalente ao .first() com order by -numero_edicao do Django)
        $historico = ApontamentoHistorico::where('apontamento_original_id', $id)
            ->orderByDesc('numero_edicao')
            ->first();
        $diffData    = [];
        $temAlteracao = false;
        if ($historico) {
            $dadosAntigos = $historico->dados_snapshot; // cast => 'array' no model
            $diffData    = $this->calcularDiff($apontamento, $dadosAntigos);
            $temAlteracao = !empty($diffData);
        }
        return view('aprovacoes.analise', [
            'apontamento'    => $apontamento,
            'historico'      => $historico,
            'diffs'          => $diffData,
            'tem_alteracao'  => $temAlteracao,
            'duracao_total'  => $apontamento->duracao_total_str,
            'usuario_editor' => $historico?->editadoPor,
        ]);
    }
    /**
     * Processa a decisão de aprovação ou rejeição.
     * Equivalente ao processar_aprovacao_view() do Django.
     *
     * POST /aprovacoes/{id}/processar
     */
    public function processar(Request $request, int $id): RedirectResponse
    {
        if ($request->method() !== 'POST') {
            return redirect()->route('aprovacoes.dashboard');
        }
        $apontamento = Apontamento::findOrFail($id);

        if (!($apontamento->status_aprovacao === 'EM_ANALISE' || ($apontamento->status_aprovacao === 'APROVADO' && $apontamento->tipo_aprovacao === 'automatica'))) {
            abort(403, 'Ação não permitida para este status.');
        }

        $request->validate([
            'acao'            => 'required|in:APROVAR,REJEITAR',
            'motivo_rejeicao' => 'required_if:acao,REJEITAR|string|nullable',
        ], [
            'motivo_rejeicao.required_if' => 'É obrigatório inserir um comentário/motivo para rejeitar o apontamento.',
        ]);
        $acao   = $request->input('acao');
        $motivo = trim($request->input('motivo_rejeicao', ''));
        if ($acao === 'APROVAR') {
            $apontamento->status_aprovacao = 'APROVADO';
            $apontamento->motivo_rejeicao  = $motivo;
            // Auditoria de aprovação manual
            $apontamento->tipo_aprovacao   = 'manual';
            $apontamento->aprovador_id     = auth()->id();
            $apontamento->data_aprovacao   = now();
            session()->flash('success', 'Registro APROVADO com sucesso.');
            AuditoriaService::registrar($request, 'APROVACAO', 'Apontamento', $id, 'Apontamento aprovado pelo Gestor.');
        } elseif ($acao === 'REJEITAR') {
            $apontamento->status_aprovacao = 'REJEITADO';
            $apontamento->motivo_rejeicao  = $motivo;
            // Registrar auditoria manual para a rejeição
            $apontamento->tipo_aprovacao   = 'manual';
            $apontamento->aprovador_id     = auth()->id();
            $apontamento->data_aprovacao   = now();
            session()->flash('warning', 'Registro REJEITADO. O colaborador foi notificado.');
            
            $nomeApontado = $apontamento->colaborador->nome_completo ?? 'Colaborador Desconhecido';
            $detalhesPayload = json_encode([
                'texto' => "Motivo: {$motivo}",
                'apontado' => $nomeApontado,
                'apontamento_id' => $apontamento->id,
                'data_apontamento' => $apontamento->data_apontamento
            ], JSON_UNESCAPED_UNICODE);
            AuditoriaService::registrar($request, 'REJEICAO', 'Apontamento', $id, $detalhesPayload);
            // FEEDBACK AUTOMÁTICO: Disparar notificação para o sino do colaborador
            if ($apontamento->colaborador_id) {
                // Tratamento seguro de data para evitar falhas no Carbon se for null
                $dataFormatada = $apontamento->data_apontamento 
                    ? \Carbon\Carbon::parse($apontamento->data_apontamento)->format('d/m/Y') 
                    : '(Data não informada)';

                // Null safe operator '?->' previne erro 500 caso o auth()->user() seja um Admin sem perfil de colaborador vinculado
                $nomeGestor = auth()->user()->colaborador?->nome_completo ?? auth()->user()->name;

                \App\Models\Notificacao::create([
                    'colaborador_id'  => $apontamento->colaborador_id,
                    'titulo'          => 'Apontamento Recusado',
                    'mensagem'        => "Seu apontamento do dia {$dataFormatada} foi recusado por {$nomeGestor}: \"{$motivo}\"",
                    'tipo'            => 'ALERTA',
                    'lida'            => false,
                    'data_referencia' => $apontamento->data_apontamento,
                    'remetente_id'    => auth()->id(),
                    'apontamento_id'  => $apontamento->id,
                ]);
            }
        }
        $apontamento->save();
        return redirect()->route('aprovacoes.dashboard');
    }
    // =========================================================================
    // HELPER: Diff de 12 campos (equivalente ao bloco if historico do Django)
    // =========================================================================
    /**
     * Calcula o diff entre o snapshot histórico e o estado atual.
     * Equivalente aos 12 blocos de comparação do analise_apontamento_view() do Django.
     *
     * @param  Apontamento $ap           Estado atual
     * @param  array       $dadosAntigos Snapshot (decoded JSON do dados_snapshot)
     * @return array       Array de ['campo', 'antes', 'depois', 'icon']
     */
    private function calcularDiff(Apontamento $ap, array $dadosAntigos): array
    {
        $diff = [];
        $formatBool = fn($v) => $v ? 'SIM' : 'NÃO';
        $formatNone = fn($v) => $v ?: '-';
        $getFkNome  = function (string $model, ?int $pk) {
            if (!$pk) {
                return '-';
            }
            try {
                $obj = $model::find($pk);
                return $obj ? (string) $obj : "(ID: {$pk} removido)";
            } catch (\Throwable) {
                return "(ID: {$pk} removido)";
            }
        };
        // 1. Hora Início
        $hIniOld = substr((string) ($dadosAntigos['hora_inicio'] ?? ''), 0, 5);
        $hIniNew = $ap->hora_inicio ? substr($ap->hora_inicio, 0, 5) : '';
        if ($hIniOld !== $hIniNew) {
            $diff[] = ['campo' => 'Hora Início', 'antes' => $hIniOld, 'depois' => $hIniNew, 'icon' => 'clock'];
        }
        // 2. Hora Término
        $hFimOld = substr((string) ($dadosAntigos['hora_termino'] ?? ''), 0, 5);
        $hFimNew = $ap->hora_termino ? substr($ap->hora_termino, 0, 5) : '';
        if ($hFimOld !== $hFimNew) {
            $diff[] = ['campo' => 'Hora Término', 'antes' => $hFimOld, 'depois' => $hFimNew, 'icon' => 'clock'];
        }
        // 3. Local
        $localOld = $dadosAntigos['local_execucao'] ?? '';
        $localNew = $ap->local_execucao;
        if ($localOld !== $localNew) {
            $mapa = ['INT' => 'DENTRO DA OBRA', 'EXT' => 'FORA DA OBRA'];
            $diff[] = ['campo' => 'Local', 'antes' => $mapa[$localOld] ?? $localOld, 'depois' => $mapa[$localNew] ?? $localNew, 'icon' => 'map'];
        }
        // 4. Projeto
        $projOldId = (int) ($dadosAntigos['projeto_id'] ?? 0) ?: null;
        $projNewId = $ap->projeto_id;
        if ($projOldId !== $projNewId) {
            $diff[] = ['campo' => 'Projeto', 'antes' => $getFkNome(Projeto::class, $projOldId), 'depois' => $ap->projeto ? (string) $ap->projeto : '-', 'icon' => 'briefcase'];
        }
        // 5. Cliente
        $cliOldId = (int) ($dadosAntigos['codigo_cliente_id'] ?? 0) ?: null;
        $cliNewId = $ap->codigo_cliente_id;
        if ($cliOldId !== $cliNewId) {
            $diff[] = ['campo' => 'Cliente', 'antes' => $getFkNome(CodigoCliente::class, $cliOldId), 'depois' => $ap->codigoCliente ? (string) $ap->codigoCliente : '-', 'icon' => 'user'];
        }
        // 6. Veículo (frota)
        $veicOldId = (int) ($dadosAntigos['veiculo_id'] ?? 0) ?: null;
        $veicNewId = $ap->veiculo_id;
        if ($veicOldId !== $veicNewId) {
            $diff[] = ['campo' => 'Veículo (Frota)', 'antes' => $getFkNome(Veiculo::class, $veicOldId), 'depois' => $ap->veiculo ? (string) $ap->veiculo : '-', 'icon' => 'car'];
        }
        // 7. Veículo manual (placa)
        $placaOld = $dadosAntigos['veiculo_manual_placa'] ?? '';
        $placaNew = $ap->veiculo_manual_placa ?? '';
        if ((string) $placaOld !== (string) $placaNew) {
            $diff[] = ['campo' => 'Veículo (Externo/Placa)', 'antes' => $formatNone($placaOld), 'depois' => $formatNone($placaNew), 'icon' => 'truck'];
        }
        // 8. Modelo veículo manual
        $modOld = $dadosAntigos['veiculo_manual_modelo'] ?? '';
        $modNew = $ap->veiculo_manual_modelo ?? '';
        if ((string) $modOld !== (string) $modNew) {
            $diff[] = ['campo' => 'Modelo Veículo (Manual)', 'antes' => $formatNone($modOld), 'depois' => $formatNone($modNew), 'icon' => 'truck'];
        }
        // 9. Em Plantão
        if ((bool) ($dadosAntigos['em_plantao'] ?? false) !== (bool) $ap->em_plantao) {
            $diff[] = ['campo' => 'Em Plantão?', 'antes' => $formatBool($dadosAntigos['em_plantao'] ?? false), 'depois' => $formatBool($ap->em_plantao), 'icon' => 'siren'];
        }
        // 10. Dorme Fora
        if ((bool) ($dadosAntigos['dorme_fora'] ?? false) !== (bool) $ap->dorme_fora) {
            $diff[] = ['campo' => 'Dorme Fora?', 'antes' => $formatBool($dadosAntigos['dorme_fora'] ?? false), 'depois' => $formatBool($ap->dorme_fora), 'icon' => 'moon'];
        }
        // 11. Observações
        $obsOld = trim((string) ($dadosAntigos['ocorrencias'] ?? ''));
        $obsNew = trim((string) ($ap->ocorrencias ?? ''));
        if ($obsOld !== $obsNew) {
            $diff[] = ['campo' => 'Observações', 'antes' => $obsOld, 'depois' => $obsNew, 'icon' => 'pencil'];
        }
        // 12. Centro de Custo
        $ccOldId = (int) ($dadosAntigos['centro_custo_id'] ?? 0) ?: null;
        $ccNewId = $ap->centro_custo_id;
        if ($ccOldId !== $ccNewId) {
            $diff[] = ['campo' => 'Centro de Custo', 'antes' => $getFkNome(CentroCusto::class, $ccOldId), 'depois' => $ap->centroCusto ? (string) $ap->centroCusto : '-', 'icon' => 'map'];
        }
        // 13. Data do Registro
        try {
            $dataOld = Carbon::parse($dadosAntigos['data_apontamento'] ?? '')->format('Y-m-d');
            $dataNew = Carbon::parse($ap->data_apontamento)->format('Y-m-d');
            
            if ($dataOld !== $dataNew) {
                $dOldFmt = Carbon::parse($dataOld)->format('d/m/Y');
                $dNewFmt = Carbon::parse($dataNew)->format('d/m/Y');
                $diff[] = ['campo' => 'Data do Registro', 'antes' => $dOldFmt, 'depois' => $dNewFmt, 'icon' => 'calendar'];
            }
        } catch (\Throwable) {}
        // 14. Auxiliar Principal
        $auxOldId = (int) ($dadosAntigos['auxiliar_id'] ?? 0) ?: null;
        $auxNewId = $ap->auxiliar_id;
        if ($auxOldId !== $auxNewId) {
            $diff[] = ['campo' => 'Auxiliar Principal', 'antes' => $getFkNome(Colaborador::class, $auxOldId), 'depois' => $ap->auxiliar?->nome_completo ?? '-', 'icon' => 'user'];
        }
        return $diff;
    }
}
