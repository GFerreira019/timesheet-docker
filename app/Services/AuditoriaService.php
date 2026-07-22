<?php

namespace App\Services;

use App\Models\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AuditoriaService
 *
 * Equivalente à função registrar_log() do utils.py do Django.
 * Responsável por criar registros na trilha de auditoria do sistema.
 *
 * Esta tabela é APPEND-ONLY — registros nunca são alterados após criação.
 *
 * Mapeamento de chamadas:
 *   Django: registrar_log(request, acao='CRIACAO', modelo='Apontamento', obj_id=ap.id, detalhes='...')
 *   Laravel: AuditoriaService::registrar($request, 'CRIACAO', 'Apontamento', $ap->id, '...')
 *
 * Para ações sem request (ex: Artisan Commands):
 *   AuditoriaService::registrarSistema('APROVACAO', 'Apontamento', $ap->id, '...')
 */
class AuditoriaService
{
    /**
     * Registra um log de auditoria a partir de uma requisição HTTP.
     *
     * Equivalente ao registrar_log(request, acao, modelo, obj_id, detalhes) do Django.
     *
     * @param Request|null $request       Requisição atual (null para ações do sistema)
     * @param string       $acao          Uma das constantes LogAuditoria::ACAO_CHOICES
     * @param string       $modelo        Nome do Model afetado (ex: 'Apontamento')
     * @param mixed        $objId         ID do objeto afetado (null para ações gerais)
     * @param string|null  $detalhes      Descrição legível do que ocorreu
     */
    public static function registrar(
        ?Request $request,
        string $acao,
        string $modelo,
        mixed $objId = null,
        ?string $detalhes = null
    ): void {
        try {
            LogAuditoria::create([
                'user_id'        => Auth::id(),
                'acao'           => $acao,
                'modelo_afetado' => $modelo,
                'objeto_id'      => $objId !== null ? (string) $objId : null,
                'detalhes'       => $detalhes,
                'ip_address'     => $request ? self::getClientIp($request) : null,
                'data_hora'      => now(),
            ]);
        } catch (\Throwable $e) {
            // Log de auditoria nunca deve derrubar a requisição
            Log::error("AuditoriaService: Falha ao registrar log. Acao={$acao}, Modelo={$modelo}. Erro: {$e->getMessage()}");
        }
    }

    /**
     * Registra uma ação do sistema (sem contexto HTTP).
     * Usado em Artisan Commands, Observers e Jobs.
     *
     * @param string      $acao      Uma das constantes LogAuditoria::ACAO_CHOICES
     * @param string      $modelo    Nome do Model afetado
     * @param mixed       $objId     ID do objeto afetado
     * @param string|null $detalhes  Descrição da ação
     * @param int|null    $userId    ID do usuário responsável (null = sistema)
     */
    public static function registrarSistema(
        string $acao,
        string $modelo,
        mixed $objId = null,
        ?string $detalhes = null,
        ?int $userId = null
    ): void {
        try {
            LogAuditoria::create([
                'user_id'        => $userId,
                'acao'           => $acao,
                'modelo_afetado' => $modelo,
                'objeto_id'      => $objId !== null ? (string) $objId : null,
                'detalhes'       => $detalhes,
                'ip_address'     => null,
                'data_hora'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("AuditoriaService: Falha ao registrar log do sistema. {$e->getMessage()}");
        }
    }

    /**
     * Obtém o IP real do cliente, respeitando proxies.
     *
     * Equivalente exato à função get_client_ip(request) do utils.py do Django.
     * Verifica os headers X-Forwarded-For e REMOTE_ADDR na mesma ordem.
     */
    public static function getClientIp(Request $request): ?string
    {
        // Equivalente ao Django: request.META.get('HTTP_X_FORWARDED_FOR')
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            // Pega o primeiro IP da lista (mais próximo do cliente)
            return trim(explode(',', $forwardedFor)[0]);
        }

        // Equivalente ao Django: request.META.get('REMOTE_ADDR')
        return $request->ip();
    }
}
