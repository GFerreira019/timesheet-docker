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

            $limit = 200;
            $offset = 0;
            $countProcessados = 0;

            do {
                $response = Http::timeout(30)
                    ->withToken($erpKey)
                    ->get($endpoint, [
                        'limit' => $limit,
                        'offset' => $offset
                    ]);

                if ($response->successful() && $response->json('success') === true) {
                    $json = $response->json();
                    $usuarios = $json['data'] ?? [];
                    $apiCount = $json['count'] ?? count($usuarios);
                    $apiTotal = $json['total'] ?? 0;

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
                                    'solides_id' => $item['tangerino_employee_id'] ?? null,
                                ]
                            );

                            if ($user->wasRecentlyCreated || $user->roles()->count() === 0) {
                                $user->assignRole('OPERACIONAL'); 
                            }

                            $countProcessados++;
                        }
                    }

                    // Controle de paginação segundo a documentação: offset + count < total
                    if (($offset + $apiCount) < $apiTotal) {
                        $offset += $limit;
                    } else {
                        break; // Fim da paginação
                    }

                } else {
                    Log::warning("ErpIntegration: Falha ao sincronizar usuários do ERP (offset: {$offset})", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    return [
                        'success' => false,
                        'message' => "Erro na API do ERP: " . $response->status(),
                        'detalhes' => $response->json()
                    ];
                }
            } while (true);

            return [
                'success' => true,
                'message' => "Sincronização de usuários concluída. {$countProcessados} registros processados.",
                'total_usuarios' => $countProcessados
            ];

        } catch (\Exception $e) {
            report($e);
            
            return [
                'success' => false,
                'message' => 'Erro de comunicação: ' . $e->getMessage(),
            ];
        }
    }
}
