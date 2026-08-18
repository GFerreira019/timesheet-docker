<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FcmController extends Controller
{
    /**
     * Recebe o token do frontend (Android WebView) e atrela ao usuário logado.
     */
    public function updateToken(Request $request): JsonResponse
    {
        Log::info('Requisição FCM recebida no backend.', ['payload' => $request->all()]);

        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            if ($user = $request->user()) {
                $user->update([
                    'fcm_token' => $request->token,
                ]);

                Log::info("FCM Token vinculado com sucesso ao usuário ID: {$user->id}");
                return response()->json(['message' => 'FCM Token armazenado com sucesso.'], 200);
            }

            Log::warning('Requisição FCM negada: Nenhum usuário autenticado encontrado na sessão.');
            return response()->json(['message' => 'Não autorizado.'], 401);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar token FCM: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Erro interno do servidor.'], 500);
        }
    }
}
