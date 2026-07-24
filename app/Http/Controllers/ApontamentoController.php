<?php

namespace App\Http\Controllers;

use App\Helpers\AcessoHelper;
use App\Http\Requests\ApontamentoRequest;
use App\Models\Apontamento;
use App\Models\ApontamentoHistorico;
use App\Models\CentroCusto;
use App\Models\CodigoCliente;
use App\Models\Colaborador;
use App\Models\Projeto;
use App\Models\Veiculo;
use App\Services\AuditoriaService;
use App\Services\ConformidadeCLTService;
use App\Services\RateioService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Ramsey\Uuid\Uuid;

/**
 * ApontamentoController
 *
 * Equivalente às views Django:
 *   apontamento_atividade_view()  → create() + store()
 *   editar_apontamento_view()     → edit() + update()
 *   excluir_apontamento_view()    → destroy()
 *   solicitar_ajuste_view()       → solicitarAjuste()
 *   aprovar_ajuste_view()         → aprovarAjuste()
 */
class ApontamentoController extends Controller
{

    // =========================================================================
    // CRIAÇÃO (apontamento_atividade_view)
    // =========================================================================

    /**
     * Exibe o formulário de novo apontamento.
     * Equivalente ao GET de apontamento_atividade_view() do Django.
     *
     * GET /apontamentos/novo
     */
    public function create(): View
    {
        $user  = auth()->user();
        $colab = $this->resolveColaborador($user);

        // Verifica check-in aberto
        $atividadeEmAndamento = false;
        $apontamentoAtivo     = null;
        $initialData          = [];

        if ($colab) {
            $apontamentoAtivo = Apontamento::where('colaborador_id', $colab->id)
                ->whereNull('hora_termino')
                ->orderByDesc('id')
                ->first();

            $atividadeEmAndamento = (bool) $apontamentoAtivo;
        }

        if ($apontamentoAtivo) {
            // Equivalente ao model_to_dict(apontamento_ativo) + preenchimento do initial do Django
            $initialData = [
                'colaborador_id'        => $apontamentoAtivo->colaborador_id,
                'data_apontamento'      => Carbon::parse($apontamentoAtivo->data_apontamento)->format('Y-m-d'),
                'hora_inicio'           => substr($apontamentoAtivo->hora_inicio, 0, 5),
                'local_execucao'        => $apontamentoAtivo->local_execucao,
                'projeto_id'            => $apontamentoAtivo->projeto_id,
                'codigo_cliente_id'     => $apontamentoAtivo->codigo_cliente_id,
                'centro_custo_id'       => $apontamentoAtivo->centro_custo_id,
                'registrar_veiculo'     => (bool) ($apontamentoAtivo->veiculo_id || $apontamentoAtivo->veiculo_manual_placa),
                'veiculo_selecao'       => $apontamentoAtivo->veiculo_id
                    ? (string) $apontamentoAtivo->veiculo_id
                    : ($apontamentoAtivo->veiculo_manual_placa ? 'OUTRO' : ''),
                'tem_auxiliar'          => (bool) $apontamentoAtivo->auxiliar_id,
                'auxiliar_id'           => $apontamentoAtivo->auxiliar_id,
                'auxiliares_extras'     => $apontamentoAtivo->auxiliaresExtras->pluck('id')->toArray(),
                'ocorrencias'           => $apontamentoAtivo->ocorrencias,
                'em_plantao'            => $apontamentoAtivo->em_plantao,
                'dorme_fora'            => $apontamentoAtivo->dorme_fora,
                'apontamento_ativo_id'  => $apontamentoAtivo->id,
            ];
        } else {
            $agora = now(); // timezone do app (America/Sao_Paulo)
            $initialData = [
                'data_apontamento' => $agora->format('Y-m-d'),
                'hora_inicio'      => $agora->format('H:i'),
            ];
        }

        return view('apontamentos.form', [
            'titulo'                   => 'Timesheet',
            'subtitulo'                => 'Preencha os dados de horário e local de trabalho.',
            'is_editing'               => false,
            'apontamento_id'           => null,
            'tipo_acao_inicial'        => 'MANUAL',
            'initial_values'           => $initialData,
            'timeline_data'            => $this->getTimelineData($colab),
            'atividade_em_andamento'   => $atividadeEmAndamento,
            'hora_inicio_em_andamento' => $atividadeEmAndamento ? substr($apontamentoAtivo->hora_inicio, 0, 5) : null,
            'pode_ratear'              => AcessoHelper::podeFazerRateio($user),
            'is_owner'                 => AcessoHelper::isOwner($user),
            'colaboradores'            => $this->getColaboradoresPermitidos($user),
            'projetos'                 => Projeto::where('ativo', true)->orderBy('nome')->get(),
            'clientes'                 => CodigoCliente::where('ativo', true)->orderBy('nome')->get(),
            'centros_custo'            => CentroCusto::where('ativo', true)->orderBy('nome')->get(),
            'veiculos'                 => Veiculo::ativos()->orderBy('placa')->get(),
            'auxiliares'               => Colaborador::ativos()
                                            ->whereHas('setorRelacionamento', fn($q) => $q->where('ativo', true))
                                            ->whereIn('cargo', ['AUXILIAR TECNICO', 'OFICIAL DE SISTEMAS'])
                                            ->orderBy('nome_completo')->get(),
        ]);
    }

    /**
     * Processa o formulário de novo apontamento.
     * Equivalente ao POST de apontamento_atividade_view() do Django.
     * Inclui lógica de Rateio (múltiplas obras) e Registro Único.
     *
     * POST /apontamentos
     */
    public function store(ApontamentoRequest $request): RedirectResponse
    {
        $user  = auth()->user();
        $data  = $request->validated();
        $colab = $this->resolveColaborador($user);

        // Bloqueia novo apontamento se há check-in aberto (equivalente ao Django)
        if ($colab && Apontamento::where('colaborador_id', $colab->id)->whereNull('hora_termino')->exists()) {
            session()->flash('error', 'Você possui uma atividade em andamento (Check-in). Finalize-a antes de iniciar outra.');
            return redirect()->route('apontamentos.create');
        }

        $dados = $request->dadosLimpos();

        // Modo START (cronômetro) — cria apontamento sem hora_termino
        if (($dados['tipo_acao'] ?? '') === 'START') {
            return $this->criarCheckIn($dados, $user);
        }

        // Verifica se é rateio
        $isRateio = AcessoHelper::podeFazerRateio($user)
            && filter_var($dados['registrar_multiplas_obras'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && !empty(trim($dados['obras_extras_list'] ?? ''));

        if ($isRateio) {
            return $this->criarComRateio($dados, $request, $user);
        }

        return $this->criarRegistroUnico($dados, $request, $user);
    }

    // =========================================================================
    // EDIÇÃO (editar_apontamento_view)
    // =========================================================================

    /**
     * Exibe o formulário de edição.
     * Equivalente ao GET de editar_apontamento_view() do Django.
     *
     * GET /apontamentos/{id}/editar
     */
    public function edit(int $id): View|RedirectResponse
    {
        $apontamento = Apontamento::findOrFail($id);
        $user        = auth()->user();

        // Segurança: só o autor ou Owner pode editar (equivalente ao Django)
        if (!AcessoHelper::isOwner($user) && $apontamento->registrado_por_id !== $user->id) {
            session()->flash('error', 'Acesso Negado: Você só pode editar seus próprios apontamentos.');
            return redirect()->route('historico.index');
        }

        // Trava rigorosa: GERENCIAL e SAC não podem editar apontamentos de terceiros,
        // mesmo que eles mesmos tenham registrado. Só podem editar se forem o colaborador alvo.
        $nivelAcesso = $user->colaborador ? strtoupper($user->colaborador->nivel_acesso) : 'OPERACIONAL';
        if (in_array($nivelAcesso, ['GERENCIAL', 'SAC'])) {
            if ($apontamento->colaborador_id !== $user->colaborador->id) {
                session()->flash('error', 'Acesso Negado: Você só pode editar apontamentos em que você seja o colaborador.');
                return redirect()->route('historico.index');
            }
        }

        // Segurança: bloqueia a edição de apontamentos em execução (Check-in ativo)
        if (is_null($apontamento->hora_termino)) {
            session()->flash('error', 'Não é possível editar um apontamento que ainda está em execução. Finalize o check-in primeiro.');
            return redirect()->route('historico.index');
        }

        // Limite de edições (equivalente ao Django: if apontamento.contagem_edicao >= 1)
        if ($apontamento->contagem_edicao >= 1 && !AcessoHelper::isOwner($user)) {
            session()->flash('error', "Limite de edição atingido. Para correções, utilize a opção 'Solicitar Ajuste'.");
            return redirect()->route('historico.index');
        }

        // Prepara initial_data com estado do veículo/auxiliar (equivalente ao Django)
        $initialData = [
            'colaborador_id'         => $apontamento->colaborador_id,
            'data_apontamento'       => Carbon::parse($apontamento->data_apontamento)->format('Y-m-d'),
            'hora_inicio'            => $apontamento->hora_inicio ? substr($apontamento->hora_inicio, 0, 5) : '',
            'hora_termino'           => $apontamento->hora_termino ? substr($apontamento->hora_termino, 0, 5) : '',
            'local_execucao'         => $apontamento->local_execucao,
            'projeto_id'             => $apontamento->projeto_id,
            'codigo_cliente_id'      => $apontamento->codigo_cliente_id,
            'centro_custo_id'        => $apontamento->centro_custo_id,
            'registrar_veiculo'      => (bool) ($apontamento->veiculo_id || $apontamento->veiculo_manual_placa),
            'veiculo_selecao'        => $apontamento->veiculo_id
                ? (string) $apontamento->veiculo_id
                : ($apontamento->veiculo_manual_placa ? 'OUTRO' : ''),
            'veiculo_manual_modelo'  => $apontamento->veiculo_manual_modelo,
            'veiculo_manual_placa'   => $apontamento->veiculo_manual_placa,
            'tem_auxiliar'           => (bool) $apontamento->auxiliar_id,
            'auxiliar_id'            => $apontamento->auxiliar_id,
            'auxiliares_extras'      => $apontamento->auxiliaresExtras->pluck('id')->toArray(),
            'em_plantao'             => $apontamento->em_plantao,
            'dorme_fora'             => $apontamento->dorme_fora,
            'ocorrencias'            => $apontamento->ocorrencias,
            'latitude'               => $apontamento->latitude,
            'longitude'              => $apontamento->longitude,
        ];

        return view('apontamentos.form', [
            'titulo'                   => 'Editar Apontamento',
            'subtitulo'                => "Editando registro (Versão {$apontamento->contagem_edicao}+1)",
            'is_editing'               => true,
            'apontamento_id'           => $apontamento->id,
            'apontamento'              => $apontamento,
            'tipo_acao_inicial'        => 'MANUAL',
            'initial_values'           => $initialData,
            'timeline_data'            => $this->getTimelineData($apontamento->colaborador),
            'atividade_em_andamento'   => false,
            'hora_inicio_em_andamento' => null,
            'pode_ratear'              => AcessoHelper::podeFazerRateio($user),
            'is_owner'                 => AcessoHelper::isOwner($user),
            'colaboradores'            => $this->getColaboradoresPermitidos($user, $apontamento),
            'projetos'                 => Projeto::where('ativo', true)->orderBy('nome')->get(),
            'clientes'                 => CodigoCliente::where('ativo', true)->orderBy('nome')->get(),
            'centros_custo'            => CentroCusto::where('ativo', true)->orderBy('nome')->get(),
            'veiculos'                 => Veiculo::where(function($q) use ($apontamento) {
                                                $q->ativos();
                                                if ($apontamento->veiculo_id) {
                                                    $q->orWhere('id', $apontamento->veiculo_id);
                                                }
                                            })->orderBy('placa')->get(),
            'auxiliares'               => Colaborador::where(function($q) use ($apontamento) {
                                                $q->ativos()->whereHas('setorRelacionamento', fn($s) => $s->where('ativo', true));
                                                if ($apontamento->auxiliar_id) {
                                                    $q->orWhere('id', $apontamento->auxiliar_id);
                                                }
                                                if ($apontamento->auxiliaresExtras()->count() > 0) {
                                                    $q->orWhereIn('id', $apontamento->auxiliaresExtras->pluck('id')->toArray());
                                                }
                                            })
                                            ->whereIn('cargo', ['AUXILIAR TECNICO', 'OFICIAL DE SISTEMAS'])
                                            ->orderBy('nome_completo')->get(),
        ]);
    }

    /**
     * Processa a atualização de um apontamento.
     * Equivalente ao POST de editar_apontamento_view() do Django.
     * Salva snapshot histórico antes de alterar.
     *
     * PUT /apontamentos/{id}
     */
    public function update(ApontamentoRequest $request, int $id): RedirectResponse
    {
        $apontamento = Apontamento::findOrFail($id);
        $user        = auth()->user();

        // Segurança (mesma da view de edição)
        if (!AcessoHelper::isOwner($user) && $apontamento->registrado_por_id !== $user->id) {
            session()->flash('error', 'Acesso Negado.');
            return redirect()->route('historico.index');
        }

        $nivelAcesso = $user->colaborador ? strtoupper($user->colaborador->nivel_acesso) : 'OPERACIONAL';
        if (in_array($nivelAcesso, ['GERENCIAL', 'SAC'])) {
            if ($apontamento->colaborador_id !== $user->colaborador->id) {
                session()->flash('error', 'Acesso Negado: Você só pode editar apontamentos em que você seja o colaborador.');
                return redirect()->route('historico.index');
            }
        }

        if ($apontamento->contagem_edicao >= 1 && !AcessoHelper::isOwner($user)) {
            session()->flash('error', 'Limite de edição atingido.');
            return redirect()->route('historico.index');
        }

        \Log::info('Dados recebidos no Update:', $request->all());

        $dados = $request->dadosLimpos();

        DB::transaction(function () use ($apontamento, $dados, $user, $request) {
            // Salva snapshot histórico (equivalente ao ApontamentoHistorico.objects.create)
            $snapshotData = $apontamento->toArray();
            unset($snapshotData['updated_at']);

            ApontamentoHistorico::create([
                'apontamento_original_id' => $apontamento->id,
                'dados_snapshot'          => $snapshotData,
                'editado_por_id'          => $user->id,
                'numero_edicao'           => $apontamento->contagem_edicao + 1,
            ]);

            // Atualiza o apontamento (equivalente ao obj = form.save(commit=False))
            $apontamento->fill([
                'colaborador_id'         => $dados['colaborador_id'],
                'data_apontamento'       => $dados['data_apontamento'],
                'hora_inicio'            => $dados['hora_inicio'],
                'hora_termino'           => $dados['hora_termino'],
                'local_execucao'         => $dados['local_execucao'],
                'projeto_id'             => $dados['projeto_id'] ?? null,
                'codigo_cliente_id'      => $dados['codigo_cliente_id'] ?? null,
                'centro_custo_id'        => $dados['centro_custo_id'] ?? null,
                'ocorrencias'            => $dados['ocorrencias'] ?? null,
                'em_plantao'             => filter_var($dados['em_plantao'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'data_plantao'           => $dados['data_plantao'] ?? null,
                'dorme_fora'             => filter_var($dados['dorme_fora'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'data_dorme_fora'        => $dados['data_dorme_fora'] ?? null,
                'latitude'               => $dados['latitude'] ?? null,
                'longitude'              => $dados['longitude'] ?? null,
                'veiculo_manual_modelo'  => $dados['veiculo_manual_modelo'] ?? null,
                'veiculo_manual_placa'   => $dados['veiculo_manual_placa'] ?? null,
                'contagem_edicao'        => $apontamento->contagem_edicao + 1,
                'status_aprovacao'       => 'EM_ANALISE',
                'motivo_rejeicao'        => null,
                // Limpa os campos de auditoria ao editar (evita dados fantasma de aprovações antigas)
                'tipo_aprovacao'         => null,
                'aprovador_id'           => null,
                'data_aprovacao'         => null,
            ]);

            // Veículo
            if (!filter_var($dados['registrar_veiculo'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $apontamento->veiculo_id = null;
                $apontamento->veiculo_manual_modelo = null;
                $apontamento->veiculo_manual_placa  = null;
            } elseif (($dados['veiculo_selecao'] ?? '') !== 'OUTRO') {
                $apontamento->veiculo_id = is_numeric($dados['veiculo_selecao'] ?? '') ? (int)$dados['veiculo_selecao'] : null;
            }

            // Auxiliar
            if (!filter_var($dados['registrar_auxiliar'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $apontamento->auxiliar_id = null;
            } else {
                $apontamento->auxiliar_id = $dados['auxiliar_id'] ?? null;
            }

            $apontamento->save();

            // Auxiliares extras (M2M) — sync recebe o array inteiro de uma vez
            $this->syncAuxiliaresExtras($apontamento, $request);

            // Auditoria
            AuditoriaService::registrar(
                request(),
                'EDICAO',
                'Apontamento',
                $apontamento->id,
                "Edição realizada (Versão {$apontamento->contagem_edicao})."
            );

            // Recalcula CLT
            $dtContabil = ConformidadeCLTService::getDataContabil(
                Carbon::parse(Carbon::parse($apontamento->data_apontamento)->format('Y-m-d') . " {$apontamento->hora_inicio}")
            );
            $colabInst = \App\Models\Colaborador::where('id_colaborador', $apontamento->colaborador_id)->first() 
                ?? \App\Models\Colaborador::find($apontamento->colaborador_id);
            ConformidadeCLTService::calcularRegrasClt($colabInst, $dtContabil);
        });

        session()->flash('success', 'Apontamento editado com sucesso! (Histórico salvo)');
        return redirect()->route('historico.index');
    }

    // =========================================================================
    // EXCLUSÃO (excluir_apontamento_view — só Owner)
    // =========================================================================

    /**
     * Exclui um apontamento.
     * Equivalente ao excluir_apontamento_view() do Django.
     *
     * DELETE /apontamentos/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $apontamento = Apontamento::findOrFail($id);
        $user        = auth()->user();
        
        $nivelAcesso = $user->colaborador ? strtoupper($user->colaborador->nivel_acesso) : 'OPERACIONAL';
        if (in_array($nivelAcesso, ['GERENCIAL', 'SAC'])) {
            if ($apontamento->colaborador_id !== $user->colaborador->id) {
                session()->flash('error', 'Acesso Negado: Você só pode excluir apontamentos em que você seja o colaborador.');
                return redirect()->route('historico.index');
            }
        }

        $colaborador = $apontamento->colaborador;

        $dtContabil = ConformidadeCLTService::getDataContabil(
            Carbon::parse(Carbon::parse($apontamento->data_apontamento)->format('Y-m-d') . " {$apontamento->hora_inicio}")
        );

        $detalhes = "Exclusão realizada. Colab: {$colaborador->nome_completo} | Data: {$apontamento->data_apontamento} | ID Original: {$id}";
        AuditoriaService::registrar(request(), 'EXCLUSAO', 'Apontamento', $id, $detalhes);

        $apontamento->delete();

        // Recalcula CLT após exclusão (equivalente ao Django)
        $colabInst = \App\Models\Colaborador::where('id_colaborador', $apontamento->colaborador_id)->first() 
            ?? \App\Models\Colaborador::find($apontamento->colaborador_id);
        ConformidadeCLTService::calcularRegrasClt($colabInst, $dtContabil);

        session()->flash('success', 'Apontamento excluído com sucesso.');
        return redirect()->route('historico.index');
    }

    // =========================================================================
    // SOLICITAÇÃO DE AJUSTE (solicitar_ajuste_view)
    // =========================================================================

    /**
     * Solicita ajuste em um apontamento fechado.
     * Equivalente ao solicitar_ajuste_view() do Django.
     *
     * POST /apontamentos/{id}/solicitar-ajuste
     */
    public function solicitarAjuste(Request $request, int $id): RedirectResponse
    {
        $apontamento = Apontamento::findOrFail($id);
        $user        = auth()->user();
        $colab       = $user?->colaborador;

        $isAutor      = $apontamento->registrado_por_id === $user->id;
        $isColaborador = $colab && $apontamento->colaborador_id === $colab->id;

        if (!$isAutor && !$isColaborador && !AcessoHelper::isOwner($user)) {
            session()->flash('error', 'Você não tem permissão para solicitar ajuste neste registro.');
            return redirect()->route('historico.index');
        }

        $nivelAcesso = $colab ? strtoupper($colab->nivel_acesso) : 'OPERACIONAL';
        if (in_array($nivelAcesso, ['GERENCIAL', 'SAC'])) {
            if (!$isColaborador) {
                session()->flash('error', 'Acesso Negado: Você só pode solicitar ajuste em apontamentos em que você seja o colaborador.');
                return redirect()->route('historico.index');
            }
        }

        $motivo = $request->input('motivo_texto');
        if ($motivo) {
            $apontamento->motivo_ajuste      = $motivo;
            $apontamento->status_aprovacao   = 'SOLICITACAO_AJUSTE';
            $apontamento->status_ajuste      = 'PENDENTE';
            $apontamento->save();

            AuditoriaService::registrar(request(), 'SOLICITACAO', 'Apontamento', $id, "Solicitou ajuste. Motivo: {$motivo}");
            session()->flash('success', 'Solicitação de ajuste enviada para a administração.');
        } else {
            session()->flash('warning', 'É necessário descrever o motivo do ajuste.');
        }

        return redirect()->route('historico.index');
    }

    /**
     * Aprovação rápida de ajuste pelo Owner.
     * Equivalente ao aprovar_ajuste_view() do Django.
     *
     * POST /apontamentos/{id}/aprovar-ajuste
     */
    public function aprovarAjuste(int $id): RedirectResponse
    {
        $apontamento = Apontamento::findOrFail($id);
        $apontamento->status_ajuste = 'APROVADO';
        $apontamento->save();

        AuditoriaService::registrar(request(), 'APROVACAO_AJUSTE', 'Apontamento', $id, 'Owner aprovou a solicitação de ajuste pendente.');
        session()->flash('success', 'Solicitação marcada como APROVADA.');

        return redirect()->route('historico.index');
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Cria um apontamento no modo Check-in (hora_termino = null).
     * Equivalente ao bloco START do Django.
     */
    private function criarCheckIn(array $dados, $user): RedirectResponse
    {
        $ap = new Apontamento();
        $this->preencherApontamento($ap, $dados, $user);
        $ap->hora_termino   = null;
        $ap->status_aprovacao = 'EM_ANALISE';
        $ap->contagem_edicao  = 0;
        $ap->save();

        // Auxiliares extras (M2M) — também no check-in
        $this->syncAuxiliaresExtras($ap, request());

        AuditoriaService::registrar(request(), 'CRIACAO', 'Apontamento', $ap->id, "Check-in iniciado às {$ap->hora_inicio}");
        session()->flash('success', 'Check-in registrado! Finalize ao encerrar a atividade.');

        return redirect()->route('apontamentos.create');
    }

    /**
     * Cria um registro único (sem rateio).
     * Equivalente ao bloco else do apontamento_atividade_view() do Django.
     */
    private function criarRegistroUnico(array $dados, Request $request, $user): RedirectResponse
    {
        $ap = new Apontamento();
        $this->preencherApontamento($ap, $dados, $user);
        $ap->status_aprovacao = 'EM_ANALISE';
        $ap->contagem_edicao  = 0;
        $ap->save();

        // Auxiliares extras (M2M) — sync recebe o array inteiro de uma vez
        $this->syncAuxiliaresExtras($ap, $request);

        $localRef = $this->resolveLocalRef($ap);
        AuditoriaService::registrar(
            $request,
            'CRIACAO',
            'Apontamento',
            $ap->id,
            "Registro único criado: {$localRef} | Horário: {$ap->hora_inicio} - {$ap->hora_termino}"
        );

        $dtContabil = ConformidadeCLTService::getDataContabil(
            Carbon::parse(Carbon::parse($ap->data_apontamento)->format('Y-m-d') . " {$ap->hora_inicio}")
        );
        $colabInst = \App\Models\Colaborador::where('id_colaborador', $ap->colaborador_id)->first() 
            ?? \App\Models\Colaborador::find($ap->colaborador_id);
        ConformidadeCLTService::calcularRegrasClt($colabInst, $dtContabil);

        session()->flash('success', "Registro de {$ap->colaborador->nome_completo} salvo com sucesso!");
        return redirect()->route('apontamentos.create');
    }

    /**
     * Cria múltiplos apontamentos rateados (distribuição proporcional).
     * Equivalente ao bloco if is_rateio: do apontamento_atividade_view() do Django.
     */
    private function criarComRateio(array $dados, Request $request, $user): RedirectResponse
    {
        // Monta lista de obras (P_{id} ou C_{id})
        $principalStr = '';
        $projetoId    = $dados['projeto_id'] ?? null;
        $clienteId    = $dados['codigo_cliente_id'] ?? null;

        if ($projetoId) {
            $principalStr = "P_{$projetoId}";
        } elseif ($clienteId) {
            $principalStr = "C_{$clienteId}";
        }

        $extrasStr   = $dados['obras_extras_list'] ?? '';
        
        if (str_starts_with(trim($extrasStr), '[')) {
            $arr = json_decode($extrasStr, true);
            $listaExtras = [];
            if (is_array($arr)) {
                foreach ($arr as $item) {
                    $listaExtras[] = is_numeric($item) ? "P_{$item}" : (string)$item;
                }
            }
        } else {
            $listaExtras = array_filter(array_map('trim', explode(',', $extrasStr)));
        }

        $todasObras  = $principalStr
            ? array_merge([$principalStr], $listaExtras)
            : $listaExtras;
            
        // Remove duplicatas e reseta as chaves numéricas
        $todasObras = array_values(array_unique($todasObras));

        if (empty($todasObras)) {
            $ap = new Apontamento();
            $this->preencherApontamento($ap, $dados, $user);
            $ap->status_aprovacao = 'EM_ANALISE';
            $ap->contagem_edicao  = 0;
            $ap->save();
            session()->flash('success', 'Registro salvo (único).');
            return redirect()->route('apontamentos.create');
        }

        // Distribui os horários proporcionalmente
        $inicio  = Carbon::parse("{$dados['data_apontamento']} {$dados['hora_inicio']}");
        $termino = Carbon::parse("{$dados['data_apontamento']} {$dados['hora_termino']}");
        $fatias  = RateioService::distribuirHorariosComGap($inicio, $termino, count($todasObras));

        $agrupamentoUid = (string) \Illuminate\Support\Str::uuid();
        $contagemSucesso = 0;

        try {
            DB::transaction(function () use (
                $todasObras, $fatias, $dados, $user, $agrupamentoUid,
                $request, &$contagemSucesso
            ) {
                foreach ($todasObras as $idx => $itemHibrido) {
                    if (!str_contains($itemHibrido, '_')) {
                        continue;
                    }

                    [$prefixo, $objIdStr] = explode('_', $itemHibrido, 2);
                    $objId = (int) $objIdStr;

                    // Valida existência (equivalente ao Projeto.objects.filter(pk=obj_id).exists())
                    if ($prefixo === 'P' && !Projeto::where('id', $objId)->exists()) {
                        continue;
                    }
                    if ($prefixo === 'C' && !CodigoCliente::where('id', $objId)->exists()) {
                        continue;
                    }

                    $ap = new Apontamento();
                    $this->preencherApontamento($ap, $dados, $user);
                    $ap->id_agrupamento   = $agrupamentoUid;
                    $ap->status_aprovacao = 'EM_ANALISE';
                    $ap->contagem_edicao  = 0;

                    // Horário da fatia proporcional
                    if (isset($fatias[$idx])) {
                        $ap->hora_inicio  = $fatias[$idx]['inicio']->format('H:i:s');
                        $ap->hora_termino = $fatias[$idx]['termino']->format('H:i:s');
                    }

                    // Obra ou Cliente (limpa o outro)
                    if ($prefixo === 'P') {
                        $ap->projeto_id        = $objId;
                        $ap->codigo_cliente_id = null;
                    } else {
                        $ap->codigo_cliente_id = $objId;
                        $ap->projeto_id        = null;
                    }

                    $ap->save();

                    // Auxiliares extras (M2M) — sync recebe o array inteiro de uma vez
                    $this->syncAuxiliaresExtras($ap, $request);

                    // Auditoria
                    $nomeObra = $ap->projeto?->nome ?? $ap->codigoCliente?->nome ?? 'Obra Indefinida';
                    AuditoriaService::registrar(
                        $request,
                        'CRIACAO',
                        'Apontamento',
                        $ap->id,
                        "Rateio automático criado: {$nomeObra} | Horário: {$ap->hora_inicio} - {$ap->hora_termino}"
                    );

                    // Recalcula CLT para cada sub-apontamento
                    $dtContabil = ConformidadeCLTService::getDataContabil(
                        Carbon::parse(Carbon::parse($ap->data_apontamento)->format('Y-m-d') . " {$ap->hora_inicio}")
                    );
                    $colabInst = \App\Models\Colaborador::where('id_colaborador', $ap->colaborador_id)->first() 
                        ?? \App\Models\Colaborador::find($ap->colaborador_id);
                    ConformidadeCLTService::calcularRegrasClt($colabInst, $dtContabil);

                    $contagemSucesso++;
                }
            });

            session()->flash('success', "Rateio realizado com sucesso: {$contagemSucesso} registros criados.");

        } catch (\Throwable $e) {
            session()->flash('error', "Erro ao salvar rateio (nenhum registro foi criado): {$e->getMessage()}");
        }

        return redirect()->route('apontamentos.create');
    }

    /**
     * Preenche os campos comuns de um Apontamento a partir dos dados validados.
     * Centraliza o preenchimento para evitar duplicação entre criarRegistroUnico e criarComRateio.
     */
    private function preencherApontamento(Apontamento $ap, array $dados, $user): void
    {
        $ap->colaborador_id      = $dados['colaborador_id'];
        $ap->data_apontamento    = $dados['data_apontamento'];
        $ap->hora_inicio         = $dados['hora_inicio'] . ':00';
        $ap->hora_termino        = isset($dados['hora_termino']) ? $dados['hora_termino'] . ':00' : null;
        $ap->local_execucao      = $dados['local_execucao'];
        $ap->projeto_id          = $dados['projeto_id'] ?? null;
        $ap->codigo_cliente_id   = $dados['codigo_cliente_id'] ?? null;
        $ap->centro_custo_id     = $dados['centro_custo_id'] ?? null;
        $ap->ocorrencias         = $dados['ocorrencias'] ?? null;
        $ap->em_plantao          = filter_var($dados['em_plantao'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $ap->data_plantao        = $dados['data_plantao'] ?? null;
        $ap->dorme_fora          = filter_var($dados['dorme_fora'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $ap->data_dorme_fora     = $dados['data_dorme_fora'] ?? null;
        $ap->latitude            = $dados['latitude'] ?? null;
        $ap->longitude           = $dados['longitude'] ?? null;
        $ap->registrado_por_id   = $user->id;

        // Veículo
        $registrarVeiculo = filter_var($dados['registrar_veiculo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($registrarVeiculo) {
            $selecao = $dados['veiculo_selecao'] ?? '';
            if ($selecao === 'OUTRO') {
                $ap->veiculo_id            = null;
                $ap->veiculo_manual_modelo = $dados['veiculo_manual_modelo'] ?? null;
                $ap->veiculo_manual_placa  = $dados['veiculo_manual_placa'] ?? null;
            } else {
                $ap->veiculo_id            = is_numeric($selecao) ? (int) $selecao : null;
                $ap->veiculo_manual_modelo = null;
                $ap->veiculo_manual_placa  = null;
            }
        } else {
            $ap->veiculo_id            = null;
            $ap->veiculo_manual_modelo = null;
            $ap->veiculo_manual_placa  = null;
        }

        // Auxiliar principal
        $registrarAuxiliar = filter_var($dados['registrar_auxiliar'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $ap->auxiliar_id = $registrarAuxiliar ? ($dados['auxiliar_id'] ?? null) : null;
    }

    /**
     * Sincroniza a relação M2M de auxiliares extras.
     * Equivalente ao apontamento.auxiliares_extras.set(ids_list) / .clear() do Django.
     *
     * IMPORTANTE: O sync() recebe o array INTEIRO de uma só vez.
     * NÃO deve estar dentro de um loop que itere sobre os IDs individuais.
     */
    private function syncAuxiliaresExtras(Apontamento $apontamento, $request): void
    {
        // Pega os dados, se não existir assume array vazio
        $auxiliares = $request->input('auxiliares', []);
        
        if (!is_array($auxiliares)) {
            $auxiliares = [];
        }

        // Limpa possíveis valores nulos ou vazios que o JS possa ter enviado por engano
        $ids = array_filter(array_map('intval', $auxiliares), fn($id) => $id > 0);
        
        // O sync cuida do resto (adiciona, remove ou limpa tudo se o array for vazio)
        $apontamento->auxiliaresExtras()->sync($ids);
    }

    /**
     * Resolve o Colaborador vinculado ao usuário logado.
     * Equivalente ao getattr(request.user, 'colaborador', None) do Django.
     */
    private function resolveColaborador($user): ?Colaborador
    {
        if (!$user) {
            return null;
        }
        return $user->colaborador ?? null;
    }

    /**
     * Retorna a referência legível do local do apontamento.
     * Equivalente ao bloco local_ref do apontamento_atividade_view() do Django.
     */
    private function resolveLocalRef(Apontamento $ap): string
    {
        if ($ap->projeto) {
            return $ap->projeto->nome;
        }
        if ($ap->codigoCliente) {
            return $ap->codigoCliente->nome;
        }
        if ($ap->centroCusto) {
            return $ap->centroCusto->nome;
        }
        return 'Local Manual';
    }

    /**
     * Retorna os colaboradores que o usuário tem permissão de selecionar.
     * Equivalente ao __init__ do ApontamentoForm do Django (filtro de queryset por role).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getColaboradoresPermitidos($user, $apontamento = null)
    {
        if (AcessoHelper::isOwner($user)) {
            return Colaborador::where(function($q) use ($apontamento) {
                $q->ativos()->whereHas('setorRelacionamento', fn($s) => $s->where('ativo', true));
                if ($apontamento && $apontamento->colaborador_id) {
                    $q->orWhere('id', $apontamento->colaborador_id);
                }
            })->orderBy('nome_completo')->get();
        }

        if (AcessoHelper::isAdministrativo($user)) {
            $colab = $user->colaborador;
            if (!$colab) {
                return Colaborador::whereRaw('0=1')->get(); // none()
            }
            $setoresGerenciados = $colab->setoresGerenciados()->pluck('setores.id');
            if ($setoresGerenciados->isEmpty()) {
                return Colaborador::where('id', $colab->id)->get();
            }
            return Colaborador::where(function ($q) use ($setoresGerenciados, $colab) {
                $q->whereIn('setor_id', $setoresGerenciados)
                  ->orWhere('id', $colab->id);
            })->where(function($q) use ($apontamento) {
                $q->ativos()->whereHas('setorRelacionamento', fn($s) => $s->where('ativo', true));
                if ($apontamento && $apontamento->colaborador_id) {
                    $q->orWhere('id', $apontamento->colaborador_id);
                }
            })->distinct()->orderBy('nome_completo')->get();
        }

        $colab = $user->colaborador;
        if (!$colab) {
            return Colaborador::whereRaw('0=1')->get();
        }

        // GERENCIAL ou SAC: pode selecionar de si mesmo e dos setores vinculados
        if (in_array($colab->nivel_acesso, ['GERENCIAL', 'SAC'])) {
            $setoresVinculados = $colab->setoresVinculados()->pluck('setores.id');
            return Colaborador::where(function ($q) use ($setoresVinculados, $colab) {
                $q->whereIn('setor_id', $setoresVinculados)
                  ->orWhere('id', $colab->id);
            })->where(function($q) use ($apontamento) {
                $q->ativos()->whereHas('setorRelacionamento', fn($s) => $s->where('ativo', true));
                if ($apontamento && $apontamento->colaborador_id) {
                    $q->orWhere('id', $apontamento->colaborador_id);
                }
            })->distinct()->orderBy('nome_completo')->get();
        }

        // GESTOR / COORDENADOR / OPERACIONAL sem ser administrativo
        return Colaborador::where('id', $colab->id)->get();
    }

    /**
     * Retorna os dados da timeline mesclados (Solides mock + Timesheet reais de hoje)
     */
    private function getTimelineData(?Colaborador $colab): array
    {
        if (!$colab) {
            return [];
        }

        $timeline = [];
        $hojeCarbon = Carbon::today();
        $hoje = $hojeCarbon->format('Y-m-d');

        // Sincronização Silenciosa JIT (Limita chamadas à API da Sólides a cada 15 min)
        $dataHoje = now()->format('Y-m-d');
        $cacheKey = "sync_solides_{$colab->id}_{$dataHoje}";

        // Verifica se o colaborador tem o ID do Sólides configurado ANTES de entrar no cache
        if (!empty($colab->solides_id)) {
            \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(15), function () use ($colab, $dataHoje, $cacheKey) {
                try {
                    // Chama o método estático correto passando o ID local (que o Service espera)
                    \App\Services\SolidesService::buscarEspelhoPonto($colab->id, $dataHoje, $dataHoje);
                    return true;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erro JIT Solides [ID: {$colab->id}]: " . $e->getMessage() . " na linha " . $e->getLine());
                    \Illuminate\Support\Facades\Cache::forget($cacheKey); // Força esquecer em caso de erro
                    return false;
                }
            });
        }

        // 1. Fetch Solides Data
        $pontosSolides = \App\Models\SolidesPonto::where('colaborador_id', $colab->id)
            ->whereDate('data', $hoje)
            ->orderBy('hora_entrada')
            ->get();

        foreach ($pontosSolides as $ps) {
            if ($ps->hora_entrada) {
                $timeline[] = [
                    'hora' => Carbon::parse($ps->hora_entrada)->format('H:i'),
                    'tipo' => 'solides',
                    'titulo' => 'Entrada',
                    'subtitulo' => 'Sólides'
                ];
            }
            if ($ps->hora_saida) {
                $timeline[] = [
                    'hora' => Carbon::parse($ps->hora_saida)->format('H:i'),
                    'tipo' => 'solides',
                    'titulo' => 'Saída',
                    'subtitulo' => 'Sólides'
                ];
            }
        }

        // 2. Fetch Timesheet Data for today
        $apontamentosHoje = Apontamento::with(['projeto', 'codigoCliente'])
            ->where('colaborador_id', $colab->id)
            ->whereDate('data_apontamento', $hoje)
            ->whereNotNull('hora_inicio')
            ->orderBy('hora_inicio')
            ->get();

        foreach ($apontamentosHoje as $ap) {
            $horaInicio = substr($ap->hora_inicio, 0, 5);
            $horaTermino = $ap->hora_termino ? substr($ap->hora_termino, 0, 5) : '--:--';
            
            $codigo = 'S/ COD';
            if ($ap->projeto) {
                $codigo = $ap->projeto->nome;
            } elseif ($ap->codigoCliente) {
                $codigo = $ap->codigoCliente->nome;
            }

            $timeline[] = [
                'tipo' => 'timesheet',
                'hora' => $horaInicio,
                'hora_fim' => $horaTermino,
                'codigo' => $codigo,
            ];
        }

        // 3. Agrupar por horário idêntico
        $timeline_collection = collect($timeline);
        $grouped_timeline = $timeline_collection->groupBy('hora')->map(function ($items, $hora) {
            $hasSolides = $items->where('tipo', 'solides')->first();
            $timesheetItems = $items->where('tipo', 'timesheet')->all();
            
            return [
                'hora' => $hora,
                'is_solides' => $hasSolides ? true : false,
                'solides_data' => $hasSolides,
                'timesheet_data' => $timesheetItems,
            ];
        })->sortBy('hora')->values()->toArray();

        return $grouped_timeline;
    }
}
