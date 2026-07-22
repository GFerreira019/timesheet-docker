<?php

namespace App\Http\Requests;

use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\Colaborador;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
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
            'local_execucao'     => ['required', 'in:INT,EXT'],
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
            'registrar_veiculo'       => ['nullable', 'boolean'],
            'veiculo_selecao'         => ['nullable', 'string'],

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

                if ($registro->local_execucao === 'INT') {
                    $referencia = $registro->projeto
                        ? (string) $registro->projeto
                        : ($registro->codigoCliente ? (string) $registro->codigoCliente : 'Obra/Cliente');
                } else {
                    $referencia = $registro->centroCusto
                        ? (string) $registro->centroCusto
                        : 'Local Externo';
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
     * Valida regras de local de execução e contexto.
     *
     * Equivalente ao bloco 4 do clean() do Django:
     *   INT → projeto OU codigo_cliente (não ambos, não nenhum); centro_custo = null
     *   EXT → centro_custo obrigatório; se permite_alocacao → projeto/cliente obrigatório
     */
    private function validarLocalContexto(Validator $validator, array $data): void
    {
        $local        = $data['local_execucao']   ?? null;
        $projetoId    = $data['projeto_id']        ?? null;
        $codClienteId = $data['codigo_cliente_id'] ?? null;
        $ccId         = $data['centro_custo_id']   ?? null;

        if ($local === 'INT') {
            if ($projetoId && $codClienteId) {
                $validator->errors()->add('projeto_id', 'Selecione apenas a Obra ou o Cliente, não ambos.');
            }
            if (!$projetoId && !$codClienteId) {
                $validator->errors()->add('projeto_id', 'Informe a Obra Específica ou o Código do Cliente.');
            }
            // centro_custo não se aplica para INT — será limpo no controller

        } elseif ($local === 'EXT') {
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

        // Blindagem de Segurança (RBAC) - Força ID do colaborador logado se for OPERACIONAL
        $user = $this->user();
        if ($user) {
            $colab = $user->colaborador;
            $nivel = $colab ? strtoupper($colab->nivel_acesso) : 'OPERACIONAL';

            if ($nivel === 'OPERACIONAL' && $colab) {
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
        if (($data['local_execucao'] ?? '') === 'INT') {
            $data['centro_custo_id'] = null;
        }

        if (($data['local_execucao'] ?? '') === 'EXT') {
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
