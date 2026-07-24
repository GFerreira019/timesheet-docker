<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class LancamentosAvancadosExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;
    protected $tipoVisao;

    public function __construct(Builder $query, $tipoVisao = 'default')
    {
        $this->query = $query;
        $this->tipoVisao = $tipoVisao;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        // Se no futuro precisar variar as colunas baseado em $this->tipoVisao, basta adicionar um switch aqui
        return [
            'Data',
            'Dia Semana',
            'Colaborador',
            'Cargo',
            'Tipo',
            'Local (Obra/Setor)',
            'Código de Obra',
            'Código Cliente',
            'Veículo',
            'Placa',
            'Hora Início',
            'Hora Fim',
            'Total Horas',
            'Plantão',
            'Dorme Fora',
            'Observações',
            'Registrado Por'
        ];
    }

    public function map($apontamento): array
    {
        // 1. Tipo, Local e Códigos
        $tipo = '';
        $local = '';
        $codigoObra = '';
        $codigoCliente = '';

        $hasObra = !empty($apontamento->projeto_id);
        $hasCliente = !empty($apontamento->codigo_cliente_id);
        $hasSetor = !empty($apontamento->centro_custo_id);

        // Define o "Tipo" conforme a ordem de precedência
        if ($hasObra) {
            $tipo = 'OBRA';
        } elseif ($hasCliente) {
            $tipo = 'CLIENTE';
        } elseif ($hasSetor) {
            $tipo = 'SETOR';
        }

        // Define "Nome Base" (Obra ou Cliente)
        $nomeBase = '';
        if ($hasObra) {
            $nomeBase = $apontamento->projeto->nome ?? '';
            $codigoObra = $apontamento->projeto->codigo ?? '';
        } elseif ($hasCliente) {
            $nomeBase = $apontamento->codigoCliente->nome ?? '';
            $codigoCliente = $apontamento->codigoCliente->codigo ?? '';
        }

        // Define "Nome do Setor"
        $nomeSetor = '';
        if ($hasSetor) {
            $nomeSetor = $apontamento->centroCusto->nome ?? '';
        }

        // Regra de concatenação do "Local"
        if ($hasSetor && ($hasObra || $hasCliente)) {
            $local = "{$nomeBase} ({$nomeSetor})";
        } elseif ($hasObra || $hasCliente) {
            $local = $nomeBase;
        } elseif ($hasSetor) {
            $local = $nomeSetor;
        }

        // 2. Dia da Semana em Português
        $diaSemana = '';
        if ($apontamento->data_apontamento) {
            $diaSemana = mb_strtoupper(Carbon::parse($apontamento->data_apontamento)->locale('pt_BR')->translatedFormat('l'), 'UTF-8');
        }

        // 3. Veículo e Placa
        $veiculoModelo = '';
        $veiculoPlaca = '';
        if ($apontamento->veiculo) {
            $veiculoModelo = $apontamento->veiculo->descricao ?? $apontamento->veiculo->modelo ?? '';
            $veiculoPlaca = $apontamento->veiculo->placa ?? '';
        } elseif ($apontamento->veiculo_manual_placa) {
            $veiculoModelo = ($apontamento->veiculo_manual_modelo ?? '') . ' (EXT.)';
            $veiculoPlaca = $apontamento->veiculo_manual_placa;
        }

        // 4. Flags de Extras
        $plantao = $apontamento->em_plantao ? 'SIM' : 'NÃO';
        $dormeFora = $apontamento->dorme_fora ? 'SIM' : 'NÃO';

        // 5. Registrado Por
        $registradoPor = 'Sistema';
        if ($apontamento->registradoPor) {
            $registradoPor = $apontamento->registradoPor->name ?: $apontamento->registradoPor->email;
        }

        return [
            $apontamento->data_apontamento ? Carbon::parse($apontamento->data_apontamento)->format('d/m/Y') : '',
            $diaSemana,
            $apontamento->colaborador->nome_completo ?? '',
            $apontamento->colaborador->cargo ?? '',
            $tipo,
            $local,
            $codigoObra,
            $codigoCliente,
            $veiculoModelo,
            $veiculoPlaca,
            $apontamento->hora_inicio ? substr($apontamento->hora_inicio, 0, 5) : '',
            $apontamento->hora_termino ? substr($apontamento->hora_termino, 0, 5) : '',
            $apontamento->duracao_total_str ?? '00:00',
            $plantao,
            $dormeFora,
            $apontamento->ocorrencias ?? '',
            $registradoPor,
        ];
    }
}
