<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoModulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class WppConnectConfigController extends Controller
{
    /**
     * Exibe o painel de configurações do WPPConnect.
     */
    public function index(): View
    {
        $apiUrl = ConfiguracaoModulo::get('WPP_API_URL', 'http://localhost:21465');
        $apiToken = ConfiguracaoModulo::get('WPP_API_TOKEN', '');
        $sessionName = ConfiguracaoModulo::get('WPP_SESSION_NAME', 'timesheet-session');

        // Checar status da API (timeout baixo para não travar a tela)
        $isOnline = false;
        if (!empty($apiUrl)) {
            try {
                // Rota padrão do WPPConnect ou apenas checa se a porta responde
                $response = Http::timeout(3)->get(rtrim($apiUrl, '/') . '/api-docs');
                if ($response->successful()) {
                    $isOnline = true;
                }
            } catch (\Exception $e) {
                $isOnline = false;
            }
        }

        return view('owner.wppconnect-config', compact('apiUrl', 'apiToken', 'sessionName', 'isOnline'));
    }

    /**
     * Salva as configurações.
     */
    public function store(Request $request)
    {
        $request->validate([
            'api_url'      => 'required|url',
            'api_token'    => 'required|string',
            'session_name' => 'required|string',
        ]);

        ConfiguracaoModulo::set('WPP_API_URL', $request->input('api_url'));
        ConfiguracaoModulo::set('WPP_API_TOKEN', $request->input('api_token'));
        ConfiguracaoModulo::set('WPP_SESSION_NAME', $request->input('session_name'));

        return redirect()->route('owner.wppconnect.index')->with('success', 'Configurações do WhatsApp atualizadas com sucesso!');
    }
}
