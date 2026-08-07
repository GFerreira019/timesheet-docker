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
        $erpUrlBase = config('services.erp.url');
        $erpKey = config('services.erp.key');

        if (!$erpUrlBase) {
            return [
                'success' => false,
                'message' => 'URL do ERP não está configurada.',
            ];
        }

        try {
            // Supondo que o endpoint seja /obras. Se for diferente, ajuste a rota aqui.
            $endpoint = rtrim($erpUrlBase, '/') . '/codigos-obra.php';
            
            $limit = 200;
            $count = 0;

            Log::info("Sincronizando obras do ERP (Sem loop) - Tentando carregar até {$limit} registros...");

            // Faz apenas uma requisição
            $response = Http::timeout(30)
                ->withToken($erpKey)
                ->get($endpoint, ['limit' => $limit]);

            if ($response->successful() && $response->json('success') === true) {
                $obras = $response->json('data');

                if (is_array($obras) && !empty($obras)) {
                    foreach ($obras as $item) {
                        if (!isset($item['codigo_obra'])) {
                            continue;
                        }

                        ErpObrasApi::updateOrCreate(
                            ['projeto_codigo' => $item['codigo_obra']],
                            [
                                'cliente_codigo' => $item['codigo_cliente'] ?? null,
                                'projeto_nome'   => $item['razao_social'] ?? null,
                                'status_ativo'   => true,
                            ]
                        );

                        $count++;
                    }
                }

                return [
                    'success' => true,
                    'message' => "Sincronização concluída (Chamada única). {$count} obras foram processadas.",
                    'total_obras' => $count
                ];
            } else {
                Log::error("Falha ao sincronizar obras do ERP (Chamada única)", [
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
            Log::error('Exceção ao sincronizar obras do ERP: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro de comunicação: ' . $e->getMessage(),
            ];
        }
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
                            ['id_usuario_erp' => $item['id_usuario']],
                            [
                                'name' => $item['nome'] ?? 'Sem Nome',
                                'email' => $item['email'] ?? null,
                            ]
                        );

                        if ($user->wasRecentlyCreated || $user->roles()->count() === 0) {
                            $user->assignRole('OPERACIONAL'); 
                        }

                        // 2. Colaborador (RH)
                        $colaborador = \App\Models\Colaborador::updateOrCreate(
                            ['id_colaborador' => $item['id_usuario']],
                            [
                                'nome_completo' => $item['nome'] ?? 'Sem Nome',
                            ]
                        );

                        // 3. A Ponte (Vinculo)
                        $user->update(['produtividade_colaborador_id' => $colaborador->id]);

                        $count++;
                    }
                }

                return [
                    'success' => true,
                    'message' => "Sincronização de usuários concluída. {$count} registros processados.",
                    'total_usuarios' => $count
                ];
            } else {
                Log::error("Falha ao sincronizar usuários do ERP", [
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
            Log::error('Exceção ao sincronizar usuários do ERP: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro de comunicação: ' . $e->getMessage(),
            ];
        }
    }
}
