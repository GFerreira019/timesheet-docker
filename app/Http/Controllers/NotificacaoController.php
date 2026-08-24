<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notificacao;
use App\Models\Colaborador;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NotificacaoController extends Controller
{
    /**
     * Logs de notificações disparadas (Push/WhatsApp) - Acesso Admin.
     *
     * GET /configuracoes/notificacoes-logs
     */
    public function index(Request $request): View
    {
        $colaboradores = Colaborador::orderBy('nome_completo')->get();

        $query = Notificacao::with('colaborador')
            ->when($request->filled('colaborador_id'), function ($q) use ($request) {
                $q->where('colaborador_id', $request->query('colaborador_id'));
            })
            ->when($request->filled('data'), function ($q) use ($request) {
                $data = $request->query('data');
                $q->where('created_at', '>=', $data . ' 00:00:00')
                  ->where('created_at', '<=', $data . ' 23:59:59');
            })
            ->when($request->filled('acao'), function ($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->query('acao') . '%');
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->query('status') === 'lidas') {
                    $q->where('lida', true);
                } elseif ($request->query('status') === 'nao_lidas') {
                    $q->where('lida', false);
                }
            })
            ->orderByDesc('created_at');
            
        // Clone para os totalizadores
        $baseQuery = clone $query;
        
        $totalPendencias = (clone $baseQuery)->where('titulo', 'like', '%Pendência de Apontamento%')->count();
        $totalAprovacoes = (clone $baseQuery)->where('titulo', 'like', '%Aprovação Pendente%')->count();
        $totalRecusados  = (clone $baseQuery)->where('titulo', 'like', '%Apontamento Recusado%')->count();
        $totalNaoLidas   = (clone $baseQuery)->where('lida', false)->count();

        $logs = $query->paginate(50)->withQueryString();

        $acoes = [
            'Pendência de Apontamento',
            'Aprovação Pendente',
            'Apontamento Recusado'
        ];

        // Regra de ouro para a lista do Modal de Permissões (quem usa o timesheet de fato)
        $colaboradoresPermissao = Colaborador::select('id', 'nome_completo', 'cargo', 'recebe_notificacao')
            ->ativos()
            ->whereHas('setorRelacionamento', function ($q) {
                $q->where('ativo', true);
            })
            ->orderBy('nome_completo')
            ->get();

        return view('notificacoes.logs', [
            'titulo'          => 'Logs de Notificações',
            'logs'            => $logs,
            'colaboradores'   => $colaboradores,
            'totalPendencias' => $totalPendencias,
            'totalAprovacoes' => $totalAprovacoes,
            'totalRecusados'  => $totalRecusados,
            'totalNaoLidas'   => $totalNaoLidas,
            'acoes'           => $acoes,
            'filtro_acao'     => $request->query('acao'),
            'filtro_data'     => $request->query('data'),
            'filtro_status'   => $request->query('status'),
            'colaboradoresPermissao' => $colaboradoresPermissao,
        ]);
    }

    /**
     * Responde a uma notificação (Adiciona justificativa e marca como lida)
     */
    public function responder(Request $request, $id)
    {
        $request->validate([
            'resposta' => 'required|string|max:1000'
        ]);

        $notificacao = Notificacao::where('id', $id)
            ->where('colaborador_id', auth()->user()->colaborador->id ?? null)
            ->first();

        if (!$notificacao) {
            return response()->json(['success' => false, 'message' => 'Notificação não encontrada ou acesso negado.'], 403);
        }

        try {
            // Conversão manual de empty strings para null (PostgreSQL)
            $notificacao->comentario_colaborador = $request->resposta !== '' ? $request->resposta : null;
            $notificacao->lida = true;
            
            // Garantir que campos numéricos/data vazios sejam null e não empty string ""
            if ($notificacao->apontamento_id === '') $notificacao->apontamento_id = null;
            if ($notificacao->remetente_id === '') $notificacao->remetente_id = null;
            if ($notificacao->data_referencia === '') $notificacao->data_referencia = null;

            $notificacao->save();

            if ($notificacao->remetente_id) {
                try {
                    $remetenteUser = \App\Models\User::find($notificacao->remetente_id);
                    $remetenteColab = $remetenteUser ? $remetenteUser->colaborador : null;
                    if ($remetenteColab) {
                        Notificacao::create([
                            'colaborador_id' => $remetenteColab->id,
                            'titulo'         => \Illuminate\Support\Str::limit($notificacao->titulo, 95) . ": ",
                            'mensagem'       => ($notificacao->colaborador?->nome_completo ?? 'Colaborador Desconhecido') . ': "' . $request->resposta . '"',
                            'tipo'           => 'INFO',
                            'data_referencia'=> $notificacao->data_referencia ?: null,
                            'remetente_id'   => auth()->id(),
                            'apontamento_id' => $notificacao->apontamento_id ?: null,
                        ]);
                    }
                } catch (\Exception $e) {
                    report($e);
                    // Continua a execução normalmente (ação secundária isolada)
                }
            }

            // Agora recuperamos o apontamento exato garantido pela coluna apontamento_id
            $apontamentoId = $notificacao->apontamento_id ?: null;
            $dataApontamento = $notificacao->data_referencia ?: null;

            $detalhesPayload = json_encode([
                'texto' => "Colaborador respondeu à notificação: '{$request->resposta}'",
                'apontado' => $notificacao->colaborador?->nome_completo ?? 'Colaborador Desconhecido',
                'apontamento_id' => $apontamentoId,
                'data_apontamento' => $dataApontamento
            ], JSON_UNESCAPED_UNICODE);

            AuditoriaService::registrar($request, 'RESPOSTA', 'Notificacao', $notificacao->id, $detalhesPayload);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
        }
    }

    /**
     * Marca uma notificação específica como lida
     */
    public function marcarLida($id)
    {
        $notificacao = Notificacao::where('id', $id)
            ->where('colaborador_id', auth()->user()->colaborador->id ?? null)
            ->first();

        if (!$notificacao) {
            return response()->json(['success' => false, 'message' => 'Notificação não encontrada ou acesso negado.'], 403);
        }

        try {
            $notificacao->lida = true;
            $notificacao->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
        }
    }

    /**
     * Marca todas as notificações do usuário como lidas
     */
    public function marcarTodasLidas()
    {
        $colaboradorId = auth()->user()->colaborador->id ?? null;
        
        if (!$colaboradorId) {
            return back()->with('error', 'Colaborador não encontrado.');
        }

        try {
            Notificacao::where('colaborador_id', $colaboradorId)
                ->where('lida', false)
                ->update(['lida' => true]);

            return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Erro ao atualizar notificações.');
        }
    }
    public function getPermissao($colaborador_id)
    {
        $colaborador = Colaborador::findOrFail($colaborador_id);
        return response()->json([
            'recebe_notificacao' => $colaborador->recebe_notificacao,
        ]);
    }

    public function togglePermissao(Request $request)
    {
        $request->validate([
            'colaborador_id' => 'required|exists:produtividade_colaborador,id',
            'recebe_notificacao' => 'required|boolean',
        ]);

        $colaborador = Colaborador::findOrFail($request->colaborador_id);
        $colaborador->recebe_notificacao = $request->recebe_notificacao;
        $colaborador->save();

        return response()->json([
            'success' => true,
            'message' => 'Permissão atualizada com sucesso.',
            'recebe_notificacao' => $colaborador->recebe_notificacao,
        ]);
    }
}
