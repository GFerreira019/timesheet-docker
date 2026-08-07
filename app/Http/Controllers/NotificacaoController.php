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
                    Log::error("Erro ao enviar notificação para o remetente: " . $e->getMessage());
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
