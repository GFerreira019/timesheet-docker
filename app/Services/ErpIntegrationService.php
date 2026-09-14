<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\ErpObrasApi;
use Illuminate\Support\Facades\Log;

class ErpIntegrationService
{
    /**
     * Sincroniza as obras retornadas da API do ERP para a tabela local.
     *
     * @return array
     */
    public function syncObras()
    {
        return [
            'success' => false,
            'message' => 'Sincronização de obras com o ERP desativada temporariamente (manutenção manual).',
        ];
    }

    /**
     * Sincroniza os usuários retornados da API do ERP.
     *
     * @return array
     */
    public function syncUsuarios()
    {
        $erpUrlBase = config('services.erp.url');
        $erpKey = config('services.erp.key');

        if (!$erpUrlBase) {
            return [
                'success' => false,
                'message' => 'URL do ERP não está configurada.',
            ];
        }

        try {
            $endpoint = rtrim($erpUrlBase, '/') . '/usuarios.php';
            
            Log::info("Sincronizando usuários do ERP...");

            $response = Http::timeout(30)
                ->withToken($erpKey)
                ->get($endpoint);

            if ($response->successful() && $response->json('success') === true) {
                $usuarios = $response->json('data');
                $count = 0;

                if (is_array($usuarios) && !empty($usuarios)) {
                    foreach ($usuarios as $item) {
                        if (!isset($item['id_usuario'])) {
                            continue;
                        }

                        // 1. User
                        $user = \App\Models\User::updateOrCreate(
                            ['connect_user_id' => $item['id_usuario']],
                            [
                                'name' => $item['nome'] ?? 'Sem Nome',
                                'email' => $item['email'] ?? null,
                            ]
                        );

                        if ($user->wasRecentlyCreated || $user->roles()->count() === 0) {
                            $user->assignRole('OPERACIONAL'); 
                        }

                        // 2. Colaborador (RH)
                        $colaborador = null;
                        if ($user->produtividade_colaborador_id) {
                            $colaborador = \App\Models\Colaborador::find($user->produtividade_colaborador_id);
                        }

                        if ($colaborador) {
                            $colaborador->update([
                                'nome_completo' => $item['nome'] ?? 'Sem Nome',
                            ]);
                        } else {
                            $colaborador = \App\Models\Colaborador::create([
                                'nome_completo' => $item['nome'] ?? 'Sem Nome',
                            ]);
                        }

                        // 3. A Ponte (Vinculo)
                        if ($user->produtividade_colaborador_id !== $colaborador->id) {
                            $user->update(['produtividade_colaborador_id' => $colaborador->id]);
                        }

                        $count++;
                    }
                }

                return [
                    'success' => true,
                    'message' => "Sincronização de usuários concluída. {$count} registros processados.",
                    'total_usuarios' => $count
                ];
            } else {
                Log::warning("ErpIntegration: Falha ao sincronizar usuários do ERP", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => "Erro na API do ERP: " . $response->status(),
                    'detalhes' => $response->json()
                ];
            }

        } catch (\Exception $e) {
            report($e);
            
            return [
                'success' => false,
                'message' => 'Erro de comunicação: ' . $e->getMessage(),
            ];
        }
    }
}
