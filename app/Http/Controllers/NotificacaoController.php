<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notificacao;
use App\Models\Colaborador;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Log;

class NotificacaoController extends Controller
{
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
            $notificacao->comentario_colaborador = $request->resposta;
            $notificacao->lida = true;
            $notificacao->save();

            if ($notificacao->remetente_id) {
                $remetenteColab = Colaborador::where('user_id', $notificacao->remetente_id)->first();
                if ($remetenteColab) {
                    Notificacao::create([
                        'colaborador_id' => $remetenteColab->id,
                        'titulo'         => $notificacao->titulo . ": ",
                        'mensagem'       => $notificacao->colaborador->nome_completo . ': "' . $request->resposta . '"',
                        'tipo'           => 'INFO',
                        'data_referencia'=> $notificacao->data_referencia,
                        'remetente_id'   => auth()->id(),
                    ]);
                }
            }

            // Agora recuperamos o apontamento exato garantido pela coluna apontamento_id
            $apontamentoId = $notificacao->apontamento_id;
            $dataApontamento = $notificacao->data_referencia;

            $detalhesPayload = json_encode([
                'texto' => "Colaborador respondeu à notificação: '{$request->resposta}'",
                'apontado' => $notificacao->colaborador->nome_completo ?? 'Colaborador Desconhecido',
                'apontamento_id' => $apontamentoId,
                'data_apontamento' => $dataApontamento
            ], JSON_UNESCAPED_UNICODE);

            AuditoriaService::registrar($request, 'RESPOSTA', 'Notificacao', $notificacao->id, $detalhesPayload);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Erro ao responder notificação: " . $e->getMessage());
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
            Log::error("Erro ao marcar notificação como lida: " . $e->getMessage());
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
            Log::error("Erro ao marcar todas notificações como lidas: " . $e->getMessage());
            return back()->with('error', 'Erro ao atualizar notificações.');
        }
    }
}
