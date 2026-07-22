<?php

namespace App\Exports;

use App\Models\Apontamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioHorasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dataInicio;
    protected $dataFim;

    public function __construct($dataInicio = null, $dataFim = null)
    {
        // Define intervalo de datas via query string (se fornecido)
        $this->dataInicio = $dataInicio ? Carbon::parse($dataInicio)->startOfDay() : null;
        $this->dataFim = $dataFim ? Carbon::parse($dataFim)->endOfDay() : null;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Apontamento::with(['colaborador', 'projeto', 'centroCusto', 'auxiliaresExtras'])
            // Ignorar rejeitados na exportação ou mantê-los se o Owner quiser tudo
            // ->where('status', '!=', 'REJEITADO')
            ->orderBy('created_at', 'asc');

        if ($this->dataInicio && $this->dataFim) {
            $query->whereBetween('created_at', [$this->dataInicio, $this->dataFim]);
        }

        $apontamentos = $query->get();
        $linhas = collect([]);

        foreach ($apontamentos as $ap) {
            // Linha principal do colaborador (Titular)
            $linhas->push([
                'tipo' => 'TITULAR',
                'apontamento' => $ap
            ]);

            // Sub-linhas dos auxiliares (se houver)
            foreach ($ap->auxiliaresExtras as $auxiliar) {
                $linhas->push([
                    'tipo' => 'AUXILIAR',
                    'apontamento' => $ap,
                    'auxiliar' => $auxiliar
                ]);
            }
        }

        return $linhas;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID Registro',
            'Data',
            'Colaborador / Auxiliar',
            'Cargo',
            'Status do Apontamento',
            'Centro de Custo',
            'Projeto / Obra',
            'Check-In',
            'Check-Out',
            'Duração Total',
            'Justificativa / Atividade'
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        $ap = $row['apontamento'];
        
        $id = $ap->id;
        $data = $ap->created_at ? $ap->created_at->format('d/m/Y') : '-';
        $status = $ap->status ?? '-';
        $centroCusto = $ap->centroCusto ? $ap->centroCusto->nome : '-';
        $projeto = $ap->projeto ? $ap->projeto->nome : '-';
        $inicio = $ap->created_at ? $ap->created_at->format('H:i') : '-';
        $fim = $ap->hora_termino ? Carbon::parse($ap->hora_termino)->format('H:i') : '-';
        // Supondo que duracao_total_str seja um accessor no Model Apontamento
        $duracao = $ap->duracao_total_str ?? '-'; 
        
        if ($row['tipo'] === 'TITULAR') {
            return [
                $id,
                $data,
                $ap->colaborador->nome ?? 'Colaborador Removido',
                $ap->colaborador->cargo ?? '-',
                $status,
                $centroCusto,
                $projeto,
                $inicio,
                $fim,
                $duracao,
                $ap->descricao ?? '-'
            ];
        } else {
            $auxiliar = $row['auxiliar'];
            return [
                $id, // Mantém o mesmo ID para referência visual
                $data,
                '   ↳ ' . ($auxiliar->nome ?? 'Desconhecido'),
                $auxiliar->cargo ?? '-',
                $status,
                $centroCusto,
                $projeto,
                $inicio,
                $fim,
                $duracao,
                '(Membro de Equipe)'
            ];
        }
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estiliza a primeira linha com negrito
            1 => ['font' => ['bold' => true]],
        ];
    }
}
