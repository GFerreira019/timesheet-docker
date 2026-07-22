<?php

namespace App\Services;

use App\Models\Colaborador;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService
 *
 * Equivalente à classe WhatsAppService do services.py do Django.
 * Integração com servidor Node.js WPPConnect/Zap-Server para envio de mensagens.
 *
 * Configuração via .env:
 *   WPP_BASE_URL=http://localhost:3000       → equivalente ao Django
 *   WPP_API_TOKEN=sua_chave_aqui             → equivalente ao Django
 *   WPP_SESSION=NOXBOT                       → nome da sessão WPP
 *
 * Endpoint utilizado: POST {WPP_BASE_URL}/api/{SESSION}/send-message
 */
class WhatsAppService
{
    // -------------------------------------------------------------------------
    // Constantes de Configuração
    // -------------------------------------------------------------------------

    /** Equivalente ao WPP_BASE_URL do settings.py Django */
    private static function getBaseUrl(): ?string
    {
        return config('services.wppconnect.base_url');
    }

    /** Equivalente ao WPP_API_TOKEN do settings.py Django */
    private static function getApiToken(): ?string
    {
        return config('services.wppconnect.token');
    }

    /** Nome da sessão WPPConnect */
    private static function getSession(): string
    {
        return config('services.wppconnect.session', 'NOXBOT');
    }

    // -------------------------------------------------------------------------
    // Métodos Públicos
    // -------------------------------------------------------------------------

    /**
     * Envia uma notificação de pendência para um colaborador via WhatsApp.
     *
     * Equivalente exato ao método Django:
     *   WhatsAppService.enviar_notificacao_pendencia(colaborador, mensagem)
     *
     * @param Colaborador $colaborador  Destinatário
     * @param string      $mensagem     Corpo da mensagem (suporta markdown WA: *bold*, _italic_)
     * @return bool                     true se enviado com sucesso, false caso contrário
     */
    public static function enviarNotificacaoPendencia(Colaborador $colaborador, string $mensagem): bool
    {
        $baseUrl  = self::getBaseUrl();
        $apiToken = self::getApiToken();
        $session  = self::getSession();

        if (!$baseUrl || !$apiToken) {
            throw new \Exception('WPP_BASE_URL ou WPP_API_TOKEN não configurado no arquivo .env.');
        }

        if (!$colaborador->telefone) {
            throw new \Exception("Colaborador '{$colaborador->nome_completo}' não possui telefone cadastrado.");
        }

        $telefone = self::formatarTelefone($colaborador->telefone);
        
        // Validação mínima de comprimento (DDI + DDD + Número) = 12 ou 13 dígitos
        if (strlen($telefone) < 12 || strlen($telefone) > 13) {
            throw new \Exception("Telefone inválido: " . $colaborador->telefone);
        }

        /**
         * Endpoint WPPConnect / Zap-Server:
         * POST /send-message
         */
        $endpoint = "{$baseUrl}/send-message";
        $payload = [
            'number'  => $telefone,
            'message' => $mensagem,
        ];

        Log::info('Tentando enviar WhatsApp', [
            'colaborador' => $colaborador->nome_completo,
            'telefone'    => $colaborador->telefone,
            'payload'     => $payload
        ]);

        $response = Http::timeout(10)
            ->withHeaders(['x-api-token' => $apiToken])
            ->post($endpoint, $payload);

        Log::info('Resposta da API WhatsApp', [
            'status' => $response->status(),
            'body'   => $response->json()
        ]);

        if ($response->failed()) {
            $erroReal = $response->body();
            throw new \Exception("A API do Node recusou o envio (Status {$response->status()}): {$erroReal}");
        }

        $json = $response->json();
        if (isset($json['status']) && $json['status'] === 'error') {
            $msgErro = $json['message'] ?? json_encode($json);
            throw new \Exception("Erro interno do WPPConnect: {$msgErro}");
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers Privados
    // -------------------------------------------------------------------------

    /**
     * Formata o telefone para o padrão internacional sem '+'.
     * Equivalente ao _formatar_telefone do Django.
     * Entrada esperada: "19987654321" → Saída: "5519987654321"
     */
    private static function formatarTelefone(string $telefone): string
    {
        // Remove qualquer caractere não numérico
        $numeros = preg_replace('/\D/', '', $telefone);

        // Já tem DDI 55 — retorna como está
        if (str_starts_with($numeros, '55') && strlen($numeros) >= 12) {
            return $numeros;
        }

        // Adiciona DDI Brasil
        return '55' . $numeros;
    }
}
