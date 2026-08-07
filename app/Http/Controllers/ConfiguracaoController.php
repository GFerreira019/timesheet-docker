<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Feriado;

class ConfiguracaoController extends Controller
{
    public function index()
    {
        // Teste de Banco de Dados
        $dbStatus = false;
        try {
            DB::connection()->getPdo();
            $dbStatus = true;
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        // Teste de Storage (Escrita)
        $storageStatus = is_writable(storage_path());

        // Recupera as configurações (Mock/Placeholder por enquanto)
        // Se houver uma tabela, trocar por: Configuracao::first() ou similar
        $config = session('configuracoes_sistema', [
            'solides_url' => '',
            'solides_token' => '',
            'wpp_instancia' => '',
            'wpp_token' => '',
            'wpp_ativar' => false,
            'feriados_provedor' => 'brasilapi',
            'feriados_ano' => date('Y'),
        ]);

        $whatsappHealth = [
            'name' => 'Servidor WhatsApp',
            'status' => 'offline',
            'message' => 'Desligado ou Inacessível',
        ];

        try {
            $url = config('services.wppconnect.base_url', 'http://localhost:3000') . '/api/status';
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get($url);
            
            if ($response->successful()) {
                $whatsappHealth['status'] = 'online';
                $data = $response->json();
                $statusInterno = $data['status'] ?? 'DESCONHECIDO';
                $whatsappHealth['message'] = "Online (Sessão: {$statusInterno})";
            } else {
                $whatsappHealth['message'] = 'Erro HTTP: ' . $response->status();
            }
        } catch (\Exception $e) {
            $whatsappHealth['message'] = 'Inacessível (Timeout ou Recusa de Conexão)';
        }

        // Teste ERP
        $erpStatus = false;
        if (config('services.erp.url')) {
            try {
                $erpUrl = rtrim(config('services.erp.url'), '/') . '/ping.php';
                $response = Http::timeout(5)
                    ->withToken(config('services.erp.key'))
                    ->get($erpUrl);
                    
                if ($response->successful()) {
                    $erpStatus = true;
                }
            } catch (\Exception $e) {
                $erpStatus = false;
            }
        }

        return view('api.dashboard', compact('dbStatus', 'storageStatus', 'config', 'whatsappHealth', 'erpStatus'));
    }

    public function salvar(Request $request)
    {
        $dados = $request->except('_token');
        
        // Simulação de salvamento das configurações
        // Configuracao::updateOrCreate(['id' => 1], $dados);
        session(['configuracoes_sistema' => $dados]);

        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }

    public function testarSolidesApi()
    {
        try {
            $token = env('SOLIDES_API_TOKEN');
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Chave SOLIDES_API_TOKEN não configurada no .env.']);
            }

            $response = \Illuminate\Support\Facades\Http::timeout(10)
                // Trocamos withToken por withHeaders para forçar o "Basic "
                ->withHeaders([
                    'Authorization' => 'Basic ' . $token
                ])
                ->get("https://api.tangerino.com.br/api/punch/realtime/enabled");

            if ($response->successful()) {
                $isRealtimeEnabled = $response->json(); 
                return response()->json([
                    'success' => true, 
                    'message' => 'Conexão estabelecida com sucesso. Token válido.',
                    'realtime_habilitado' => $isRealtimeEnabled
                ]);
            }
            
            $erroDetalhado = $response->json() ?? $response->body();

            return response()->json([
                'success' => false, 
                'message' => 'Erro ' . $response->status() . ' na API do Tangerino.',
                'detalhes' => $erroDetalhado
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Falha de comunicação: ' . $e->getMessage()]);
        }
    }

    public function statusWhatsapp()
    {
        // Simulação do Node.js
        return response()->json([
            'status' => 'online',
            'message' => 'Node.js (PM2): Online (Porta 3000)',
            'logs' => [
                date('H:i', strtotime('-10 mins')) . ' Mensagem enviada p/ Gabriel (Alerta de Atraso)',
                date('H:i', strtotime('-45 mins')) . ' Mensagem enviada p/ Alex'
            ],
            'qr_code' => false // false = conectado, true = base64 image
        ]);
    }

    public function testarFeriadosApi()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withToken(env('FERIADOS_API_KEY'))
                ->get("https://feriadosapi.com/api/v1/feriados/nacionais?ano=" . date('Y'));

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Conexão estabelecida com sucesso. Token válido.']);
            }
            
            return response()->json(['success' => false, 'message' => 'Erro ' . $response->status() . ': ' . $response->body()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Falha de comunicação: ' . $e->getMessage()]);
        }
    }

    public function testarERP()
    {
        try {
            $erpUrlBase = config('services.erp.url');
            if (!$erpUrlBase) {
                return response()->json([
                    'success' => false, 
                    'message' => 'URL do ERP não configurada.'
                ]);
            }

            $erpUrl = rtrim($erpUrlBase, '/') . '/ping.php';
            
            $response = Http::timeout(5)
                ->withToken(config('services.erp.key'))
                ->get($erpUrl);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ping realizado com sucesso.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro HTTP ' . $response->status() . ' - ' . $response->body()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
