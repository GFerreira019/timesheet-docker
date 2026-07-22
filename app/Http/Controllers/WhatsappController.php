<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colaborador;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * Exibe a tela de automação do WhatsApp.
     */
    public function index()
    {
        return view('whatsapp.index');
    }

    /**
     * Retorna o status atual da sessão do WPPConnect consumindo a API Node.
     * Sempre retorna 'conectado' (bool) e 'status_raw' (string) para o frontend.
     */
    public function statusSessao()
    {
        // Status considerados OK pela API do WPPConnect / Venom-bot
        $statusValidos = ['inChat', 'CONNECTED', 'isLogged', 'MAIN'];

        try {
            $url = config('services.wppconnect.base_url', 'http://localhost:3000') . '/api/status';
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get($url);
            
            if ($response->successful()) {
                $json = $response->json();
                $statusNode = $json['status'] ?? '';
                
                $json['conectado']  = in_array($statusNode, $statusValidos);
                $json['status_raw'] = $statusNode; // Sempre repassa a string exata p/ debug
                
                return response()->json($json);
            }
            return response()->json(['status' => 'OFFLINE', 'status_raw' => 'OFFLINE', 'conectado' => false], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'OFFLINE', 'status_raw' => 'NODE_OFFLINE', 'conectado' => false], 500);
        }
    }

    /**
     * Encerra forçadamente o processo do Node.js.
     */
    public function pararServidorNode(Request $request)
    {
        try {
            exec('taskkill /F /IM node.exe 2>&1', $output, $return_var);
            
            if ($return_var === 0 || $return_var === 128) { // 0: sucesso, 128: não encontrado
                \Illuminate\Support\Facades\Log::info('Comando taskkill disparado.', ['output' => $output]);
                return redirect()->route('whatsapp.index')
                    ->with('success', 'O serviço do Node.js foi encerrado com sucesso.');
            }
            
            return redirect()->route('whatsapp.index')
                ->with('success', 'Comando de parada executado (O serviço já devia estar parado).');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao tentar parar Node: ' . $e->getMessage());
            return redirect()->route('whatsapp.index')
                ->withErrors(['erro' => 'Falha ao executar o comando de parada: ' . $e->getMessage()]);
        }
    }

    /**
     * Força a inicialização do Node.js em background (Windows) para testes.
     */
    public function iniciarServidorNode(Request $request)
    {
        try {
            $porta = config('services.wppconnect.port', 3000);
            $conexao = @fsockopen('127.0.0.1', $porta, $errno, $errstr, 1);
            
            if (is_resource($conexao)) {
                fclose($conexao);
                return redirect()->route('whatsapp.index')
                    ->withErrors(['erro' => 'A porta ' . $porta . ' já está em uso. O servidor Node já está a rodar em background!']);
            }

            // 1. Definição estrita dos caminhos
            $pastaNode = base_path('zap-server');
            $arquivoNode = 'server.js';
            $nomeLog = 'whatsapp_node.log';
            $arquivoLog = storage_path('logs/' . $nomeLog);
            
            // 2. Monta o comando do Windows:
            // - start "" /B: Título de janela vazio para evitar bugs de aspas no CMD
            // - escapeshellarg(): Blinda os caminhos corretamente
            // - cd /d: Força a mudança de diretório
            $comando = 'start "" /B cmd /c "cd /d ' . escapeshellarg($pastaNode) . ' && node ' . escapeshellarg($arquivoNode) . ' >> ' . escapeshellarg($arquivoLog) . ' 2>&1"';
            
            // 3. Execução assíncrona
            pclose(popen($comando, 'r'));
            
            \Illuminate\Support\Facades\Log::info('Comando Node disparado.', ['comando' => $comando]);

            return redirect()->route('whatsapp.index')
                ->with('success', 'Comando enviado ao servidor Node. Se não conectar em instantes, verifique o arquivo de log: storage/logs/' . $nomeLog);
                
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao tentar iniciar Node: ' . $e->getMessage());
            return redirect()->route('whatsapp.index')
                ->withErrors(['erro' => 'Falha ao executar o comando: ' . $e->getMessage()]);
        }
    }

    /**
     * Testa o envio de mensagem instanciando um Colaborador mock e chamando o WhatsAppService.
     */
    public function enviarTeste(Request $request)
    {
        $request->validate([
            'telefone' => 'required|string',
            'mensagem' => 'required|string',
        ]);

        try {
            // Mock de colaborador apenas com o telefone
            $colaborador = new Colaborador();
            $colaborador->nome_completo = 'Colaborador de Teste';
            $colaborador->telefone = $request->input('telefone');

            $mensagem = $request->input('mensagem');

            $sucesso = WhatsAppService::enviarNotificacaoPendencia($colaborador, $mensagem);

            if ($sucesso) {
                return back()->with('success', 'Mensagem de teste enviada com sucesso ao serviço!');
            } else {
                return back()->withErrors(['erro' => 'O WhatsAppService retornou FALSE sem detalhes. Verifique se a API do WPPConnect está respondendo.']);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Node está desligado ou porta errada
            return back()->withErrors(['erro' => 'Servidor Node não respondeu (Connection Refused/Timeout). Certifique-se de que a URL no .env do Laravel está correta. Detalhe: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            // Outros erros (ex: Sessão não conectada, erro 404, 500)
            Log::error("WhatsappController: Erro no teste de envio - " . $e->getMessage());
            return back()->withErrors(['erro' => 'Erro na API Node: ' . $e->getMessage()]);
        }
    }
}
