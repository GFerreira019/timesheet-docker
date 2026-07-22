<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Feriado;
use App\Services\FeriadoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeriadoController extends Controller
{
    /**
     * Tela principal de Gestão de Feriados e Localidades.
     *
     * Descobre as cidades dos colaboradores ativos, identifica pendências
     * de feriados municipais e lista todos os feriados cadastrados.
     */
    public function index()
    {
        $anoAtual = (int) date('Y');

        // 1. Descobrir cidades mapeadas (onde há colaboradores)
        $cidadesMonitoradas = Colaborador::select('cidade', 'uf')
            ->whereNotNull('cidade')
            ->where('cidade', '!=', '')
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->distinct()
            ->get()
            ->map(function ($item) {
                return (object) [
                    'cidade' => $item->cidade,
                    'uf'     => strtoupper(trim($item->uf)),
                ];
            });

        // 2. Identificar cidades pendentes (sem nenhum feriado no ano atual)
        $cidadesPendentes = $cidadesMonitoradas->filter(function ($local) use ($anoAtual) {
            return !Feriado::whereYear('data', $anoAtual)
                ->whereRaw('UPPER(TRIM(cidade)) = ?', [strtoupper(trim($local->cidade))])
                ->whereRaw('UPPER(TRIM(uf)) = ?', [strtoupper(trim($local->uf))])
                ->exists();
        })->values();

        // 3. Cidades atendidas = todas - pendentes
        $cidadesAtendidas = $cidadesMonitoradas->filter(function ($local) use ($cidadesPendentes) {
            return !$cidadesPendentes->contains(function ($pendente) use ($local) {
                return strtoupper(trim($pendente->cidade)) === strtoupper(trim($local->cidade))
                    && strtoupper(trim($pendente->uf)) === strtoupper(trim($local->uf));
            });
        })->values();

        // 4. Lista geral de feriados
        $feriados = Feriado::orderBy('cidade', 'asc')
            ->orderBy('data', 'asc')
            ->get();

        // 5. Agrupados por data+descricao (para visualização consolidada)
        //    Ex: "01/01/2026 - Ano Novo" → [Campinas/SP, São Paulo/SP, ...]
        $feriadosAgrupados = $feriados->groupBy(function ($f) {
            return $f->data->format('Y-m-d') . '|' . $f->descricao;
        })->map(function ($grupo) {
            $primeiro = $grupo->first();
            return (object) [
                'data'       => $primeiro->data,
                'descricao'  => $primeiro->descricao,
                'tipo'       => $primeiro->tipo ?? null,
                'manual'     => $primeiro->inserido_manualmente ?? false,
                'cidades'    => $grupo->map(fn($f) => mb_strtoupper(\Illuminate\Support\Str::ascii($f->cidade)) . '/' . mb_strtoupper($f->uf))->unique()->values(),
                'quantidade' => $grupo->count(),
                'ids'        => $grupo->pluck('id')->toArray(),
            ];
        })->values();

        return view('feriados.index', compact(
            'cidadesMonitoradas',
            'cidadesPendentes',
            'cidadesAtendidas',
            'feriados',
            'feriadosAgrupados',
            'anoAtual'
        ));
    }

    /**
     * Sincroniza feriados nacionais e tenta sincronizar municipais
     * para todas as cidades monitoradas.
     */
    public function sincronizar(Request $request)
    {
        $ano = $request->input('ano', (int) date('Y'));
        $resultados = [];
        $temPendencias = false;

        try {
            // Sincronizar nacionais
            FeriadoService::sincronizarApi($ano);
            $resultados[] = '✅ Feriados nacionais sincronizados com sucesso.';
        } catch (\Exception $e) {
            $resultados[] = '❌ Falha nos nacionais: ' . $e->getMessage();
            $temPendencias = true;
        }

        // Sincronizar municipais para cada cidade dos colaboradores
        $cidades = Colaborador::select('cidade', 'uf')
            ->whereNotNull('cidade')
            ->where('cidade', '!=', '')
            ->whereNotNull('uf')
            ->where('uf', '!=', '')
            ->distinct()
            ->get();

        foreach ($cidades as $local) {
            $ok = FeriadoService::sincronizarFeriadosMunicipais($ano, $local->cidade, $local->uf);
            if ($ok) {
                $resultados[] = "✅ {$local->cidade}/{$local->uf}: Feriados municipais sincronizados.";
            } else {
                $possuiFeriados = Feriado::whereYear('data', $ano)
                    ->whereRaw('UPPER(TRIM(cidade)) = ?', [strtoupper(trim($local->cidade))])
                    ->whereRaw('UPPER(TRIM(uf)) = ?', [strtoupper(trim($local->uf))])
                    ->exists();
                
                if (!$possuiFeriados) {
                    $resultados[] = "⚠️ {$local->cidade}/{$local->uf}: Sem dados na API — cadastro manual necessário.";
                    $temPendencias = true;
                }
            }
        }

        if (!$temPendencias) {
            return redirect()->route('feriados.index')
                ->with('success', '✅ Sincronização concluída com sucesso. Nenhuma cidade pendente.');
        }

        return redirect()->route('feriados.index')
            ->with('success', implode("\n", $resultados));
    }

    /**
     * Cadastra um feriado manualmente.
     *
     * REGRA LEGADA: cidade e uf são obrigatórios (NOT NULL na tabela).
     * Todo feriado — mesmo nacional — é salvo vinculado a uma cidade.
     */
    public function cadastrarManual(Request $request)
    {
        $request->validate([
            'data'       => 'required|date',
            'descricao'  => 'required|string|max:255',
            'localidade' => 'required|string',
        ]);

        list($cidade, $uf) = explode('|', $request->input('localidade'));

        Feriado::create([
            'data'                 => $request->input('data'),
            'descricao'            => $request->input('descricao'),
            'cidade'               => mb_strtoupper(\Illuminate\Support\Str::ascii($cidade)),
            'uf'                   => $uf,
            'tipo'                 => 'municipal',
            'inserido_manualmente' => true,
        ]);

        return redirect()->route('feriados.index')
            ->with('success', "Feriado '{$request->descricao}' cadastrado com sucesso!");
    }

    /**
     * Exclui um feriado.
     */
    public function deletar($id)
    {
        $feriado = Feriado::findOrFail($id);
        $desc = $feriado->descricao;
        $feriado->delete();

        return redirect()->route('feriados.index')
            ->with('success', "Feriado '{$desc}' excluído com sucesso!");
    }
}
