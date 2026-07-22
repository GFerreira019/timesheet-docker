<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;
use App\Services\SolidesService;
use Carbon\Carbon;

class PontoController extends Controller
{
    /**
     * Exibe a view de busca e a tabela do espelho de ponto.
     */
    public function index(Request $request)
    {
        // 1. Carregar lista de colaboradores ativos para o filtro
        $colaboradores = Colaborador::ativos()->orderBy('nome_completo')->get();

        $pontos = null;
        
        $colaboradorId = $request->input('colaborador_id');
        $mesAno = $request->input('mes_ano'); // formato YYYY-MM

        if ($colaboradorId && $mesAno) {
            try {
                $inicioMes = Carbon::parse($mesAno . '-01')->startOfMonth()->format('Y-m-d');
                $fimMes = Carbon::parse($mesAno . '-01')->endOfMonth()->format('Y-m-d');

                // 2. Chamar o service para buscar o espelho de ponto na API da Sólides
                $pontos = SolidesService::buscarEspelhoPonto($colaboradorId, $inicioMes, $fimMes);

            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['api' => 'Erro ao consultar espelho de ponto: ' . $e->getMessage()]);
            }
        }

        return view('pontos.index', compact('colaboradores', 'pontos', 'colaboradorId', 'mesAno'));
    }
}
