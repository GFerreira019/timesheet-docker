<?php

namespace App\Http\Controllers;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AuditoriaController
 *
 * Equivalente à view Django:
 *   dashboard_auditoria_view() → index()
 *
 * Requer is_owner (middleware 'owner').
 */
class AuditoriaController extends Controller
{
    /**
     * Trilha de auditoria com filtros por usuário, ação e data.
     * Equivalente ao dashboard_auditoria_view() do Django.
     *
     * GET /owner/auditoria
     */
    public function index(Request $request): View
    {
        $totalNotificacoes = \App\Models\Notificacao::count();
        $colaboradores = \App\Models\Colaborador::orderBy('nome_completo')->get();

        if ($request->query('filtro') === 'notificacoes') {
            $query = \App\Models\Notificacao::with('colaborador')
                ->when($request->filled('colaborador_id'), function ($q) use ($request) {
                    $q->where('colaborador_id', $request->query('colaborador_id'));
                })
                ->when($request->filled('data'), function ($q) use ($request) {
                    $q->whereDate('created_at', $request->query('data'));
                })
                ->when($request->filled('tipo'), function ($q) use ($request) {
                    $q->where('tipo', $request->query('tipo'));
                })
                ->orderByDesc('created_at');
                
            $logs = $query->paginate(50)->withQueryString();
        } else {
            $query = LogAuditoria::with('user')
                ->when($request->filled('user'), function ($q) use ($request) {
                    $q->where('user_id', $request->query('user'));
                })
                ->when($request->filled('acao'), function ($q) use ($request) {
                    $q->where('acao', $request->query('acao'));
                })
                ->when($request->filled('data'), function ($q) use ($request) {
                    // Otimização de índice: evita whereDate que aplica DATE() no banco
                    $data = $request->query('data');
                    $q->where('data_hora', '>=', $data . ' 00:00:00')
                      ->where('data_hora', '<=', $data . ' 23:59:59');
                })
                ->orderByDesc('data_hora');

            $logs = $query->paginate(50)->withQueryString();
        }

        // Lista de usuários para o filtro
        $usuarios = User::orderBy('name')->get();

        return view('owner.auditoria', [
            'titulo'       => 'Trilha de Auditoria',
            'logs'         => $logs,
            'usuarios'     => $usuarios,
            'colaboradores'=> $colaboradores,
            'filtro_user'  => $request->query('user') ? (int) $request->query('user') : '',
            'filtro_acao'  => $request->query('acao'),
            'filtro_data'  => $request->query('data'),
            'totalNotificacoes' => $totalNotificacoes,
            'acoes'        => LogAuditoria::ACAO_CHOICES,
        ]);
    }
}
