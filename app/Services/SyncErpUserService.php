<?php

namespace App\Services;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Sincronização de Usuários do ERP
 *
 * Implementa o padrão "Registro Fantasma" (Stub) para garantir integridade
 * referencial mesmo quando o payload do usuário chega antes do colaborador
 * ser completamente sincronizado.
 *
 * Exemplo de payload recebido:
 * {
 *   "id_usuario": 1,
 *   "id_colaborador": 45698524568,
 *   "nome": "Administrador",
 *   "email": "admin@atgbsistemas.com.br",
 *   "status_descricao": "Ativo"
 * }
 *
 * Fluxo de execução:
 * 1. Extrai o id_colaborador do payload
 * 2. firstOrCreate em produtividade_colaborador (cria fantasma se não existir)
 * 3. Usa o id interno (PK) do colaborador obtido
 * 4. updateOrCreate em users, associando produtividade_colaborador_id
 */
class SyncErpUserService
{
    /**
     * Processa um payload de usuário vindo do ERP e persiste no banco.
     *
     * @param  array  $payload  Dados brutos vindos da API do ERP
     * @return User             O usuário criado ou atualizado
     *
     * @throws \Throwable  Re-lança qualquer exceção após rollback da transação
     */
    public function handle(array $payload): User
    {
        return DB::transaction(function () use ($payload) {

            // ----------------------------------------------------------------
            // 1. Extrair o id_colaborador do payload
            // ----------------------------------------------------------------
            // Código de 11 dígitos que identifica o colaborador no ERP
            $idColaborador = (string) ($payload['id_colaborador'] ?? null);
            // Nome do usuário vindo do payload — usado no rascunho e no User
            $nomeCompleto  = trim((string) ($payload['nome'] ?? ''));
            // E-mail: chave de busca única para o registro em users
            $email         = trim((string) ($payload['email'] ?? ''));
            // ID da plataforma Sólides (diferente do id_colaborador do ERP)
            // Usado para sincronização de espelho de ponto
            $solidesId     = isset($payload['solides_id']) ? (string) $payload['solides_id'] : null;

            if (empty($idColaborador)) {
                throw new \InvalidArgumentException(
                    'Payload do ERP não contém o campo obrigatório "id_colaborador".'
                );
            }
            if (empty($email)) {
                throw new \InvalidArgumentException(
                    'Payload do ERP não contém o campo obrigatório "email".'
                );
            }

            // ----------------------------------------------------------------
            // 2. Ghost Record — busca ou cria em produtividade_colaborador
            //
            //    Chave de busca: id_colaborador (código de 11 dígitos do ERP).
            //
            //    Se o registro NÃO existir, cria um RASCUNHO com apenas:
            //      • id_colaborador → código de 11 dígitos do ERP
            //      • nome_completo  → nome vindo do payload
            //      • nivel_acesso   → OBRIGATORIAMENTE 'OPERACIONAL'
            //    Os demais campos (cargo, cidade, etc.) ficam nulos e serão
            //    preenchidos manualmente pelo administrador depois.
            //
            //    ⚠ ORDEM IMPORTA: o colaborador DEVE ser salvo ANTES do User
            //    para não violar a FK do PostgreSQL:
            //      users.produtividade_colaborador_id → produtividade_colaborador.id
            // ----------------------------------------------------------------
            /** @var Colaborador $colaborador */
            $colaborador = Colaborador::firstOrCreate(
                // — Critério de busca: código de 11 dígitos do ERP —
                ['id_colaborador' => $idColaborador],
                // — Dados usados APENAS na criação do rascunho —
                [
                    'nome_completo' => $nomeCompleto ?: null,
                    // nivel_acesso é OBRIGATÓRIO mesmo em registros rascunho
                    'nivel_acesso'  => 'OPERACIONAL',
                ]
            );

            if ($colaborador->wasRecentlyCreated) {
                Log::info("[SyncErpUserService] Rascunho de colaborador criado (id_colaborador={$idColaborador}).", [
                    // PK interna gerada pelo banco — é este valor que vai em users.produtividade_colaborador_id
                    'colaborador_pk' => $colaborador->id,
                    'nome_completo'  => $colaborador->nome_completo,
                    'nivel_acesso'   => $colaborador->nivel_acesso,
                ]);
            }

            // ----------------------------------------------------------------
            // 3. Captura o id interno (PK auto-incremental) do colaborador
            // ----------------------------------------------------------------
            $produtividadeColaboradorId = $colaborador->id;

            // ----------------------------------------------------------------
            // 4. Cria ou atualiza o registro em users
            //
            //    DISTINÇÃO CRÍTICA de colunas:
            //
            //    users.id_usuario_erp              → payload["id_usuario"]
            //                                        Código do USUÁRIO no ERP.
            //                                        Diferente do id_colaborador.
            //
            //    users.produtividade_colaborador_id → $colaborador->id
            //                                        PK interna (bigInt sequencial)
            //                                        gerada pelo banco no passo 2.
            //                                        NUNCA recebe os 11 dígitos do ERP.
            //
            //    Como o colaborador já foi salvo no passo 2, a FK é satisfeita.
            // ----------------------------------------------------------------
            $user = User::updateOrCreate(
                // Critério de busca: e-mail único do usuário
                ['email' => $email],
                [
                    'name'                         => $nomeCompleto,
                    // Código do usuário no ERP (payload["id_usuario"])
                    'id_usuario_erp'               => (string) ($payload['id_usuario'] ?? null),
                    // PK interna do colaborador — satisfaz a FK do PostgreSQL
                    'produtividade_colaborador_id' => $colaborador->id,
                    // ID Sólides — fonte de verdade para sincronização de ponto
                    // null se o ERP não enviar este campo (nem sempre disponível)
                    'solides_id'                   => $solidesId,
                ]
            );

            Log::info("[SyncErpUserService] Usuário sincronizado e liberado para acesso: id={$user->id}, email={$user->email}", [
                'user_id'                      => $user->id,
                'produtividade_colaborador_id' => $colaborador->id,
                // Código de 11 dígitos — armazenado em produtividade_colaborador.id_colaborador
                'id_colaborador_erp'           => $idColaborador,
                'solides_id'                   => $solidesId,
                'colaborador_era_rascunho'     => $colaborador->wasRecentlyCreated,
            ]);

            return $user;
        });
    }

    /**
     * Processa um array de payloads em lote.
     * Útil para importações iniciais ou resync completo do ERP.
     *
     * @param  array  $payloads  Lista de payloads
     * @return array{synced: int, errors: array}
     */
    public function handleBatch(array $payloads): array
    {
        $synced = 0;
        $errors = [];

        foreach ($payloads as $index => $payload) {
            try {
                $this->handle($payload);
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'index'   => $index,
                    'payload' => $payload,
                    'error'   => $e->getMessage(),
                ];
                Log::error("[SyncErpUserService] Erro ao processar payload #{$index}: {$e->getMessage()}", [
                    'payload' => $payload,
                ]);
            }
        }

        return compact('synced', 'errors');
    }
}
