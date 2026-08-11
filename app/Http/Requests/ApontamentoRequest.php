<?php

namespace App\Http\Requests;

use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\CentroCusto;
use App\Models\SolidesPonto;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * ApontamentoRequest
 *
 * Equivalente completo ao ApontamentoForm do forms.py do Django.
 * Replica fielmente os 8 grupos de validação do método clean() original.
 *
 * Grupos de Validação:
 *   0. Modo START (Cronômetro)    → clearErrors('hora_termino') + allow null
 *   1. RBAC / Permissões de Rateio → bloqueia rateio sem permissão
 *   2. Bloqueio de Datas Futuras  → inicio/termino não podem ser futuros
 *   3. Detecção de Conflitos      → mesmo dia + interjornada do dia anterior
 *   4. Regras de Local e Contexto → INT requer projeto/cliente; EXT requer centro_custo
 *   5. Validação de Veículos      → híbrido: frota cadastrada vs. manual
 *   6. Validação de Auxiliares    → auxiliar_id obrigatório se registrar_auxiliar
 *   7. Validação de Plantão       → data_plantao deve bater com data_apontamento
 *   8. Validação de Rateio        → obras_extras_list obrigatório se registrar_multiplas_obras
 *
 * Controle de Acesso (RBAC) — equivalente ao __init__ do Django:
 *   - Owner       → pode ver/selecionar qualquer colaborador
 *   - ADMINISTRATIVO → colaboradores dos setores gerenciados + si mesmo
 *   - GESTOR/COORDENADOR/Padrão → apenas a si mesmo (campo locked)
 */
class ApontamentoRequest extends FormRequest
{
    // =========================================================================
    // AUTORIZAÇÃO
    // =========================================================================

    /**
     * Determina se o usuário tem permissão para fazer este request.
     * A autorização granular (RBAC de colaborador) é feita em withValidator().
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    // =========================================================================
    // REGRAS BASE (equivalente ao Meta + campos required do Django)
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isModoStart = $this->input('tipo_acao') === 'START';

        return [
            // Campos obrigatórios base
            'colaborador_id'     => ['required', 'integer', 'exists:produtividade_colaborador,id'],
            'data_apontamento'   => ['required', 'date'],
            'local_execucao'     => ['required', 'in:EXTERNO,INTERNO'],
            'hora_inicio'        => ['required', 'date_format:H:i'],

            // hora_termino é nullable no modo START (cronômetro)
            'hora_termino'       => $isModoStart
                ? ['nullable', 'date_format:H:i']
                : ['required', 'date_format:H:i'],

            // Campos opcionais de localização
            'projeto_id'         => ['nullable', 'integer', 'exists:produtividade_projeto,id'],
            'codigo_cliente_id'  => ['nullable', 'integer', 'exists:produtividade_codigocliente,id'],
            'centro_custo_id'    => ['nullable', 'integer', 'exists:produtividade_centrocusto,id'],

            // Veículo
            'veiculo_id'              => ['nullable', 'integer', 'exists:produtividade_veiculo,id'],
            'veiculo_manual_modelo'   => ['nullable', 'string', 'max:100'],
            'veiculo_manual_placa'    => ['nullable', 'string', 'max:20'],
            'registrar_veiculo'       => [
                \Illuminate\Validation\Rule::requiredIf(function () {
                    $local = $this->input('local_execucao');
                    if ($local === 'EXTERNO') return true;
                    if ($local === 'INTERNO' && $this->input('centro_custo_id')) {
                        $cc = \App\Models\CentroCusto::find($this->input('centro_custo_id'));
                        return $cc && mb_strtoupper($cc->nome, 'UTF-8') === 'REVISAO DE VEICULO';
                    }
                    return false;
                }),
                'boolean'
            ],
            'veiculo_selecao'         => [
                \Illuminate\Validation\Rule::requiredIf(function () {
                    $local = $this->input('local_execucao');
                    if ($local === 'EXTERNO') return true;
                    if ($local === 'INTERNO' && $this->input('centro_custo_id')) {
                        $cc = \App\Models\CentroCusto::find($this->input('centro_custo_id'));
                        return $cc && mb_strtoupper($cc->nome, 'UTF-8') === 'REVISAO DE VEICULO';
                    }
                    return false;
                }),
                'nullable',
                'string'
            ],

            // Equipe
            'registrar_auxiliar'      => ['nullable', 'boolean'],
            'auxiliar_id'             => ['nullable', 'integer', 'exists:produtividade_colaborador,id'],
            'auxiliares'              => ['nullable', 'array'],
            'auxiliares.*'            => ['integer', 'exists:produtividade_colaborador,id'],
            'auxiliares_extras_list'  => ['nullable', 'string'],

            // Adicionais de folha
            'em_plantao'       => ['nullable', 'boolean'],
            'data_plantao'     => ['nullable', 'date'],
            'dorme_fora'       => ['nullable', 'boolean'],
            'data_dorme_fora'  => ['nullable', 'date'],

            // Rateio
            'registrar_multiplas_obras' => ['nullable', 'boolean'],
            'obras_extras_list'         => ['nullable', 'string'],

            // Controle
            'tipo_acao'   => ['nullable', 'string'],
            'ocorrencias' => ['nullable', 'string'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    // =========================================================================
    // VALIDAÇÕES CRUZADAS (equivalente ao clean() do Django)
    // =========================================================================

    /**
     * Configura o validador com todas as regras de negócio cruzadas.
     * Equivalente exato ao método clean() do ApontamentoForm do Django.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $data = $this->all();
            $user = $this->user();
            $colab = $user ? $user->colaborador : null;

            // ==================================================================
            // -1. VALIDAÇÃO DE SEGURANÇA PARA TERCEIROS
            // ==================================================================
            $colaboradorIdReq = $data['colaborador_id'] ?? null;
            if ($colaboradorIdReq && $colab && $colaboradorIdReq != $colab->id) {
                $podeLancarTerceiros = $user->hasAnyRole(['ADMIN', 'GERENCIAL', 'SAC', 'ADMINISTRATIVO']) || AcessoHelper::isOwner($user);

                if (!$podeLancarTerceiros) {
                    $validator->errors()->add('colaborador_id', 'Acesso Negado: Você não tem permissão para lançar/editar apontamentos em nome de outros colaboradores.');
                } elseif (!$user->hasRole('ADMIN') && !AcessoHelper::isOwner($user)) {
                    $setoresPermitidos = collect();

                    if ($user->hasAnyRole(['GERENCIAL', 'SAC'])) {
                        $setoresVinculados = $colab->setoresVinculados()->pluck('setores.id');
                        $setoresGerenciados = $colab->setoresGerenciados()->pluck('setores.id');
                        $setoresPermitidos = $setoresVinculados->merge($setoresGerenciados)->unique();
                    } elseif ($user->hasRole('ADMINISTRATIVO')) {
                        $setoresPermitidos = $colab->setoresGerenciados()->pluck('setores.id');
                    }

                    $colabAlvo = Colaborador::find($colaboradorIdReq);
                    if (!$colabAlvo || !$setoresPermitidos->contains($colabAlvo->setor_id)) {
                        $validator->errors()->add('colaborador_id', 'Acesso Negado: O colaborador selecionado não pertence aos setores que você gerencia.');
                    }
                }
            }

            // ==================================================================
            // 0. MODO START (Cronômetro/Check-in)
            // ==================================================================
            if (($data['tipo_acao'] ?? '') === 'START') {
                $validator->errors()->forget('hora_termino');
                // IMPORTANTE: NÃO retornar aqui para que as regras de negócio de Obra, Cliente e Centro de Custo sejam aplicadas!
            }

            $colaboradorId    = $data['colaborador_id'] ?? null;
            $dataApontamento  = $data['data_apontamento'] ?? null;
            $horaInicio       = $data['hora_inicio'] ?? null;
            $horaTermino      = $data['hora_termino'] ?? null;
            
            $temCamposBase = $colaboradorId && $dataApontamento && $horaInicio && $horaTermino;

            // ==================================================================
            // 1. RBAC / Permissões de Rateio
            // ==================================================================
            if ($user && !AcessoHelper::podeFazerRateio($user)) {
                $data['registrar_multiplas_obras'] = false;
                $data['obras_extras_list']         = '';
            }

            // ==================================================================
            // 2. BLOQUEIO DE DATAS FUTURAS
            // ==================================================================
            if ($temCamposBase || (($data['tipo_acao'] ?? '') === 'START' && $colaboradorId && $dataApontamento && $horaInicio)) {
                try {
                    $agora     = now();
                    $dtInicio  = Carbon::parse("{$dataApontamento} {$horaInicio}");
                    
                    if ($dtInicio->gt($agora)) {
                        $validator->errors()->add('hora_inicio', 'O horário de início não pode ser no futuro.');
                    }
                    
                    if ($horaTermino) {
                        $dtTermino = Carbon::parse("{$dataApontamento} {$horaTermino}");
                        if ($dtTermino->lt($dtInicio)) {
                            $dtTermino->addDay();
                        }
                        if ($dtTermino->gt($agora)) {
                            $validator->errors()->add('hora_termino', 'O horário de término não pode ser no futuro.');
                        }
                    }
                } catch (\Throwable) {
                    // Ignora falhas de parse de data; as validações base pegarão.
                }

                // ==============================================================
                // 3. CONFLITOS DE HORÁRIO / OVERLAP (MEMÓRIA)
                // ==============================================================
                if ($horaTermino) {
                    $this->validarConflitosHorario($validator, (int) $colaboradorId, $dataApontamento, $horaInicio, $horaTermino);
                }

                // ==============================================================
                // 3.1 CONFLITO COM INTERVALO DE ALMOÇO DA SÓLIDES
                // ==============================================================
                if ($horaTermino) {
                    $this->validarIntervalSolides($validator, (int) $colaboradorId, $dataApontamento, $horaInicio, $horaTermino);
                }
            }

            // ==================================================================
            // 4. VALIDAÇÃO DE CONTEXTO (INT x EXT)
            // ==================================================================
            $this->validarLocalContexto($validator, $data);

            // ==================================================================
            // 5. VALIDAÇÃO DE VEÍCULOS
            // ==================================================================
            $this->validarVeiculo($validator, $data);

            // ==================================================================
            // 6. VALIDAÇÃO DE AUXILIARES
            // ==================================================================
            if (filter_var($data['registrar_auxiliar'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                if (empty($data['auxiliar_id'])) {
                    $validator->errors()->add('auxiliar_id', 'Selecione o Auxiliar.');
                }
            }

            // ==================================================================
            // 7. VALIDAÇÃO DE PLANTÃO
            // ==================================================================
            if (filter_var($data['em_plantao'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $dataPlantao = $data['data_plantao'] ?? null;
                if (!$dataPlantao) {
                    $validator->errors()->add('data_plantao', 'Selecione a Data do Plantão no calendário.');
                } elseif ($dataApontamento && $dataPlantao !== $dataApontamento) {
                    try {
                        $dpCarbon  = Carbon::parse($dataPlantao)->toDateString();
                        $daCarbon  = Carbon::parse($dataApontamento)->toDateString();
                        if ($dpCarbon !== $daCarbon) {
                            $validator->errors()->add('data_plantao', 'A Data do Plantão deve ser a mesma do registro principal.');
                        }
                    } catch (\Throwable) {}
                }
            }

            // ==================================================================
            // 8. VALIDAÇÃO DE RATEIO (Múltiplas Obras)
            // ==================================================================
            if (filter_var($data['registrar_multiplas_obras'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $extras = $data['obras_extras_list'] ?? '';
                if (empty(trim((string) $extras))) {
                    $validator->errors()->add(
                        'registrar_multiplas_obras',
                        'Erro de processamento: Nenhuma obra adicional foi detectada para o rateio.'
                    );
                }
            }
        });
    }

    // =========================================================================
    // HELPERS INTERNOS
    // =========================================================================

    /**
     * Validação de conflitos de horário em memória usando Coleções e Carbon.
     */
    private function validarConflitosHorario(
        Validator $validator,
        int $colaboradorId,
        string $dataApontamento,
        string $horaInicio,
        string $horaTermino
    ): void {
        // Normaliza a data para Y-m-d
        try {
            $dataApontamentoObj = Carbon::createFromFormat('Y-m-d', $dataApontamento);
        } catch (\Throwable) {
            try {
                $dataApontamentoObj = Carbon::createFromFormat('d/m/Y', $dataApontamento);
            } catch (\Throwable) {
                return;
            }
        }
        
        $dataFmt = $dataApontamentoObj->format('Y-m-d');
        $dataAnteriorFmt = (clone $dataApontamentoObj)->subDay()->format('Y-m-d');

        $apontamentoId = $this->route('apontamento')?->id ?? $this->route('id') ?? null;

        $horaInicioStr = substr($horaInicio, 0, 5);
        $horaTerminoStr = substr($horaTermino, 0, 5);
        
        $inicioAtual = Carbon::createFromFormat('Y-m-d H:i', "{$dataFmt} {$horaInicioStr}");
        $terminoAtual = Carbon::createFromFormat('Y-m-d H:i', "{$dataFmt} {$horaTerminoStr}");

        if ($terminoAtual->lt($inicioAtual)) {
            $terminoAtual->addDay();
        }

        // Busca registros (hoje e ontem) no banco usando Eloquent
        $registrosBanco = Apontamento::where('colaborador_id', $colaboradorId)
            ->where(function ($q) use ($dataFmt, $dataAnteriorFmt) {
                $q->whereDate('data_apontamento', $dataFmt)
                  ->orWhereDate('data_apontamento', $dataAnteriorFmt);
            })
            ->whereNotNull('hora_termino')
            ->where('status_aprovacao', '!=', 'REJEITADO')
            ->get();

        foreach ($registrosBanco as $registro) {
            // Garante que não é o próprio registro sendo editado
            if ($apontamentoId && $registro->id == $apontamentoId) {
                continue;
            }

            // Captura segura da data do banco
            $regDataFmt = $registro->data_apontamento instanceof \Carbon\Carbon 
                ? $registro->data_apontamento->format('Y-m-d')
                : substr((string)$registro->data_apontamento, 0, 10);

            // Montagem blindada do Banco
            $horaIniBanco = substr($registro->hora_inicio, 0, 5);
            $horaFimBanco = substr($registro->hora_termino, 0, 5);

            $inicioBanco = \Carbon\Carbon::parse("{$regDataFmt} {$horaIniBanco}");
            $terminoBanco = \Carbon\Carbon::parse("{$regDataFmt} {$horaFimBanco}");

            if ($terminoBanco->lt($inicioBanco)) {
                $terminoBanco->addDay(); // Overnight do banco
            }

            // A Fórmula Universal de Interseção: (A_ini < B_fim) E (A_fim > B_ini)
            if ($inicioAtual->lt($terminoBanco) && $terminoAtual->gt($inicioBanco)) {
                
                // Adiciona os erros de validação que disparam o redirecionamento (302) automático do Laravel
                $colabNome = $registro->colaborador->nome_completo ?? 'Colaborador';
                
                $validator->errors()->add('__conflito__', 'Overlap de horários detectado.');
                $validator->errors()->add('hora_inicio', "Conflito: {$colabNome} já possui apontamento neste horário.");
                $validator->errors()->add('hora_termino', 'Ajuste os horários e tente novamente.');

                // Prepara a carga de dados para o Modal Tailwind acordar na tela
                $dtConfFmt = $registro->data_apontamento instanceof \Carbon\Carbon 
                    ? $registro->data_apontamento->format('d/m/Y')
                    : \Carbon\Carbon::parse($registro->data_apontamento)->format('d/m/Y');

                $tipoConflito = $regDataFmt === $dataFmt 
                    ? 'Conflito de Horário (Mesmo dia)' 
                    : 'Conflito Interjornada (Dia Anterior)';

                if ($registro->local_execucao === 'EXTERNO') {
                    $referencia = $registro->projeto
                        ? (string) $registro->projeto
                        : ($registro->codigoCliente ? (string) $registro->codigoCliente : 'Obra/Cliente');
                } else {
                    $referencia = $registro->centroCusto
                        ? (string) $registro->centroCusto
                        : 'Atividade Interna';
                }

                session()->flash('conflito_details', [
                    'tipo'        => $tipoConflito,
                    'colaborador' => $colabNome,
                    'referencia'  => $referencia,
                    'data'        => $dtConfFmt,
                    'inicio'      => $horaIniBanco,
                    'termino'     => $horaFimBanco
                ]);

                break; // Para o loop assim que achar o primeiro overlap
            }
        }
    }

    /**
     * Valida se o horário do apontamento conflita com o intervalo de almoço
     * registrado na Sólides para o colaborador no mesmo dia.
     *
     * Estratégia:
     *  - Consulta a tabela local `solides_pontos` (já sincronizada via SolidesService)
     *    com um Cache::remember de 15 minutos para não reprocessar a query a cada request.
     *  - Se o colaborador não tiver `solides_id`, ignora silenciosamente.
     *  - O intervalo de almoço é identificado como o GAP entre o fim da 1ª batida
     *    e o início da 2ª batida do dia (entrada/saída/retorno/saída).
     *  - Fórmula de sobreposição: (apont_inicio < intervalo_fim) && (apont_fim > intervalo_inicio)
     */
    private function validarIntervalSolides(
        Validator $validator,
        int $colaboradorId,
        string $dataApontamento,
        string $horaInicio,
        string $horaTermino
    ): void {
        // 1. Carrega o colaborador e verifica se possui integração com a Sólides
        $colaborador = \App\Models\Colaborador::find($colaboradorId);

        if (!$colaborador || empty($colaborador->solides_id)) {
            // Sem solides_id → validação não se aplica, ignora silenciosamente
            return;
        }

        $solidesId = $colaborador->solides_id;

        // Normaliza a data para Y-m-d
        try {
            $dataFmt = Carbon::parse($dataApontamento)->format('Y-m-d');
        } catch (\Throwable) {
            return;
        }

        // 2. Camada de Cache (TTL: 15 minutos)
        //    Chave única por colaborador + data → evita N queries por request
        $cacheKey = "solides_pontos_{$solidesId}_{$dataFmt}";

        /** @var \Illuminate\Support\Collection $batidasDoDia */
        $batidasDoDia = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($colaboradorId, $dataFmt) {
            try {
                // Consulta o banco local sincronizado (mais rápido e resiliente que chamar a API em tempo real)
                return \App\Models\SolidesPonto::where('colaborador_id', $colaboradorId)
                    ->whereDate('data', $dataFmt)
                    ->whereNotNull('hora_entrada')
                    ->orderBy('hora_entrada')
                    ->get(['hora_entrada', 'hora_saida']);
            } catch (\Exception $e) {
                Log::warning("validarIntervalSolides: Falha ao consultar batidas da Sólides para colaborador {$colaboradorId} em {$dataFmt}. Validação ignorada. Erro: {$e->getMessage()}");
                return collect(); // Falha silenciosa → não bloqueia o apontamento
            }
        });

        // 3. Monta os horários do apontamento para comparação (feito 1x)
        try {
            $apInicio  = Carbon::createFromFormat('Y-m-d H:i', "{$dataFmt} " . substr($horaInicio, 0, 5));
            $apTermino = Carbon::createFromFormat('Y-m-d H:i', "{$dataFmt} " . substr($horaTermino, 0, 5));
            
            if ($apTermino->lt($apInicio)) {
                $apTermino->addDay(); // Overnight
            }
        } catch (\Throwable) {
            return;
        }

        // 4. Detecta os intervalos (gaps) entre turnos
        //    O intervalo de descanso é o tempo ENTRE a hora_saida de um turno
        //    e a hora_entrada do turno seguinte.
        if ($batidasDoDia->count() < 2) {
            return; // Sem dados suficientes para detectar intervalo
        }

        for ($i = 0; $i < $batidasDoDia->count() - 1; $i++) {
            $turnoAtual   = $batidasDoDia->get($i);
            $proximoTurno = $batidasDoDia->get($i + 1);

            // Blindagem contra turnos abertos (onde o colaborador ainda não bateu a saída)
            if (empty($turnoAtual->hora_saida) || empty($proximoTurno->hora_entrada)) {
                continue; // Dados incompletos neste gap, pula para o próximo
            }

            try {
                $inicioIntervaloObj = Carbon::createFromFormat('Y-m-d H:i', "{$dataFmt} " . substr($turnoAtual->hora_saida, 0, 5));
                $fimIntervaloObj    = Carbon::createFromFormat('Y-m-d H:i', "{$dataFmt} " . substr($proximoTurno->hora_entrada, 0, 5));
            } catch (\Throwable) {
                continue; // Falha no parse deste gap, pula
            }

            // Sanidade: o intervalo deve ser positivo
            if (!$fimIntervaloObj->gt($inicioIntervaloObj)) {
                continue;
            }

            $inicioFmt      = $inicioIntervaloObj->format('H:i');
            $fimFmt         = $fimIntervaloObj->format('H:i');
            $apontInicioFmt = $apInicio->format('H:i');
            $apontFimFmt    = $apTermino->format('H:i');

            Log::info("Validando Sólides - Apontamento: {$apontInicioFmt} às {$apontFimFmt} | Intervalo Banco: {$inicioFmt} às {$fimFmt}");

            // 5. Fórmula Universal de Sobreposição: (A_ini < B_fim) && (A_fim > B_ini)
            if ($apInicio->lt($fimIntervaloObj) && $apTermino->gt($inicioIntervaloObj)) {
                session()->flash('conflito_details', [
                    'tipo'        => 'Conflito de Intervalo (Sólides)',
                    'colaborador' => $colaborador->nome_completo ?? $colaborador->nome ?? "ID {$colaboradorId}",
                    'referencia'  => "Intervalo registrado na Sólides",
                    'data'        => Carbon::parse($dataFmt)->format('d/m/Y'),
                    'inicio'      => $inicioFmt,
                    'termino'     => $fimFmt,
                ]);

                $validator->errors()->add(
                    'hora_inicio',
                    "Apontamento bloqueado: O horário conflita com o seu intervalo de almoço registrado na Sólides das {$inicioFmt} às {$fimFmt}."
                );

                break; // Bloqueou em um gap, interrompe o loop
            }
        }
    }

    /**
     * Valida regras de local de execução e contexto.
     *
     * Equivalente ao bloco 4 do clean() do Django:
     *   EXTERNO → colaborador em campo; projeto OU codigo_cliente obrigatório; centro_custo = null
     *   INTERNO → colaborador na base; centro_custo obrigatório; se permite_alocacao → projeto/cliente obrigatório
     */
    private function validarLocalContexto(Validator $validator, array $data): void
    {
        $local        = $data['local_execucao']   ?? null;
        $projetoId    = $data['projeto_id']        ?? null;
        $codClienteId = $data['codigo_cliente_id'] ?? null;
        $ccId         = $data['centro_custo_id']   ?? null;

        if ($local === 'EXTERNO') {
            if ($projetoId && $codClienteId) {
                $validator->errors()->add('projeto_id', 'Selecione apenas a Obra ou o Cliente, não ambos.');
            }
            if (!$projetoId && !$codClienteId) {
                $validator->errors()->add('projeto_id', 'Informe a Obra Específica ou o Código do Cliente.');
            }
            // centro_custo não se aplica para EXTERNO — será limpo no controller

        } elseif ($local === 'INTERNO') {
            if (!$ccId) {
                $validator->errors()->add('centro_custo_id', 'Selecione o Setor / Justificativa (Custo).');
            }

            if ($ccId) {
                $cc = \App\Models\CentroCusto::find($ccId);
                if ($cc && $cc->permite_alocacao) {
                    // Equivalente ao Django: if centro_custo.permite_alocacao:
                    if (!$projetoId && !$codClienteId) {
                        $validator->errors()->add('projeto_id', 'Para esta Justificativa, é OBRIGATÓRIO informar Obra/Cliente.');
                    }
                    if ($projetoId && $codClienteId) {
                        $validator->errors()->add('projeto_id', 'Selecione apenas a Obra ou o Cliente, não ambos.');
                    }
                }
                // Se !permite_alocacao → projeto e codigo_cliente serão nullificados no controller
            }
        }
    }

    /**
     * Valida o bloco híbrido de veículos.
     *
     * Equivalente ao bloco 5 do clean() do Django:
     *   - registrar_veiculo = false → limpa todos os campos de veículo
     *   - veiculo_selecao = 'OUTRO' → modelo e placa manual obrigatórios
     *   - veiculo_selecao = ID      → veículo da frota (modelo/placa manual = null)
     *   - Higienização: placa em uppercase, sem '-' e ' '
     */
    private function validarVeiculo(Validator $validator, array $data): void
    {
        if (!filter_var($data['registrar_veiculo'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return; // Sem veículo — controller limpa os campos
        }

        $selecao = $data['veiculo_selecao'] ?? null;

        if (!$selecao) {
            $validator->errors()->add('veiculo_selecao', 'Selecione um veículo.');
            return;
        }

        if ($selecao === 'OUTRO') {
            // Veículo manual
            $mod = trim($data['veiculo_manual_modelo'] ?? '');
            $pla = trim($data['veiculo_manual_placa']  ?? '');

            if (!$mod) {
                $validator->errors()->add('veiculo_manual_modelo', 'Informe o Modelo.');
            }

            if (!$pla) {
                $validator->errors()->add('veiculo_manual_placa', 'Informe a Placa.');
            } else {
                // Higienização: uppercase, remove '-' e ' ' (equivalente ao Django)
                $plaLimpa = strtoupper(str_replace(['-', ' '], '', $pla));
                if (strlen($plaLimpa) !== 7) {
                    $validator->errors()->add(
                        'veiculo_manual_placa',
                        "A placa deve ter 7 caracteres (Digitado: " . strlen($plaLimpa) . ")."
                    );
                }
            }
        }
        // Se é ID de veículo cadastrado — sem validação adicional; controller seta veiculo_id
    }

    // =========================================================================
    // MENSAGENS CUSTOMIZADAS
    // =========================================================================

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'colaborador_id.required'   => 'Selecione o Colaborador.',
            'colaborador_id.exists'     => 'Colaborador inválido ou não encontrado.',
            'data_apontamento.required' => 'Informe a Data do Apontamento.',
            'data_apontamento.date'     => 'Data inválida.',
            'local_execucao.required'   => 'Selecione o Local de Execução.',
            'local_execucao.in'         => 'Local de Execução inválido.',
            'hora_inicio.required'      => 'Informe a Hora de Início.',
            'hora_inicio.date_format'   => 'Hora de Início inválida (use HH:MM).',
            'hora_termino.required'     => 'Informe a Hora de Término.',
            'hora_termino.date_format'  => 'Hora de Término inválida (use HH:MM).',
            'projeto_id.exists'         => 'Projeto não encontrado.',
            'codigo_cliente_id.exists'  => 'Código de Cliente não encontrado.',
            'centro_custo_id.exists'    => 'Centro de Custo não encontrado.',
            'veiculo_id.exists'         => 'Veículo não encontrado na frota.',
            'auxiliar_id.exists'        => 'Auxiliar não encontrado.',
        ];
    }

    // =========================================================================
    // PREPARAÇÃO DOS DADOS
    // =========================================================================

    /**
     * Prepara e higieniza os dados antes da validação.
     * Equivalente ao código de normalização do clean() do Django.
     */
    protected function prepareForValidation(): void
    {
        $placa = $this->input('veiculo_manual_placa');
        if ($placa) {
            // Higienização da placa: uppercase, remove traços e espaços
            $this->merge([
                'veiculo_manual_placa' => strtoupper(str_replace(['-', ' '], '', $placa)),
            ]);
        }

        $modelo = $this->input('veiculo_manual_modelo');
        if ($modelo) {
            $this->merge([
                'veiculo_manual_modelo' => strtoupper(trim($modelo)),
            ]);
        }

        // Se for check-in, injeta os dados de agora caso não venham do front
        if ($this->input('tipo_acao') === 'START') {
            $agora = now();
            $this->merge([
                'data_apontamento' => $this->input('data_apontamento') ?: $agora->toDateString(),
                'hora_inicio'      => $this->input('hora_inicio') ?: $agora->format('H:i'),
            ]);
        }

        // Blindagem de Segurança (RBAC) - Força ID do colaborador logado se for OPERACIONAL puro
        // MIGRADO: substituiu leitura de nivel_acesso por helpers do AcessoHelper
        $user = $this->user();
        if ($user) {
            $colab = $user->colaborador;
            $naoPodeLancarTerceiros = !AcessoHelper::podeLancarPorTerceiros($user);

            if ($naoPodeLancarTerceiros && $colab) {
                $this->merge([
                    'colaborador_id'    => $colab->id,
                    'cargo_colaborador' => $colab->cargo
                ]);
            }
        }
    }

    /**
     * Retorna os dados validados com os campos higienizados/derivados.
     * Usado pelo Controller para popular o model Apontamento.
     *
     * Equivalente ao retorno do cleaned_data do Django.
     *
     * @return array<string, mixed>
     */
    public function dadosLimpos(): array
    {
        $data = $this->validated();
        $user = $this->user();

        // Modo START: garante hora_termino = null
        if (($data['tipo_acao'] ?? '') === 'START') {
            $data['hora_termino'] = null;
        }

        // Remove campos de rateio se sem permissão
        if ($user && !AcessoHelper::podeFazerRateio($user)) {
            $data['registrar_multiplas_obras'] = false;
            $data['obras_extras_list']         = '';
        }

        // Limpa campos de local contrários ao local_execucao selecionado
        if (($data['local_execucao'] ?? '') === 'EXTERNO') {
            // Em campo (Dentro da Obra): limpa centro_custo, mantém projeto/cliente
            $data['centro_custo_id'] = null;
        }

        if (($data['local_execucao'] ?? '') === 'INTERNO') {
            // Na base (Fora da Obra): limpa projeto/cliente se CC não permite alocação
            $ccId = $data['centro_custo_id'] ?? null;
            if ($ccId) {
                $cc = \App\Models\CentroCusto::find($ccId);
                if ($cc && !$cc->permite_alocacao) {
                    // Equivalente ao Django: cleaned_data['projeto'] = None; cleaned_data['codigo_cliente'] = None
                    $data['projeto_id']        = null;
                    $data['codigo_cliente_id'] = null;
                }
            }
        }

        // Limpa campos de veículo se não registrar_veiculo
        if (!filter_var($data['registrar_veiculo'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $data['veiculo_id']            = null;
            $data['veiculo_manual_modelo'] = null;
            $data['veiculo_manual_placa']  = null;
        } elseif (($data['veiculo_selecao'] ?? '') !== 'OUTRO') {
            // Veículo da frota — limpa campos manuais
            $data['veiculo_manual_modelo'] = null;
            $data['veiculo_manual_placa']  = null;
            $data['veiculo_id']            = is_numeric($data['veiculo_selecao'] ?? '') ? (int)$data['veiculo_selecao'] : null;
        }

        return $data;
    }
}
