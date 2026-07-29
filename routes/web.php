<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApontamentoController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\AprovacaoController;
use App\Http\Controllers\ConformidadeController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PontoController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuporteController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\Api\CalendarioApiController;
use App\Livewire\Gerencial\Dashboard;
use App\Livewire\Gerencial\LancamentosAvancado;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rota de entrada
Route::get('/', function () {
    return app()->environment('local') ? redirect('/dev/painel') : redirect()->route('painel');
})->name('home');

Route::get('/login', function () {
        if (app()->environment('local')) {
            return redirect()->route('dev.login');
        }

        // TODO: Substituir pelo redirecionamento oficial do SSO do ERP
        // Exemplo futuro: return redirect()->to('https://sso.seu-erp.com.br/authorize?client_id=...');
        return "Em breve: Redirecionamento para o login do ERP";
    })->name('login');

    // TODO: Substituir pelo redirecionamento de logout do SSO do ERP
    Route::post('/logout', function () {
        Auth::logout(); // Opcional: Efetua o logout do laravel temporariamente para evitar falhas de sessão caso o usuário clique
        return redirect()->route('login');
    })->name('logout');

Route::middleware('auth')->group(function () {
    // Rotas Base (Home e Configurações)
    Route::get('/painel', [DashboardController::class, 'index'])->name('painel');

    // Suporte
    Route::prefix('suporte')->name('suporte.')->group(function () {
        Route::get('/', [SuporteController::class, 'index'])->name('index');
        Route::post('/', [SuporteController::class, 'store'])->name('store');
        Route::get('/ticket/{ticket}/anexo', [SuporteController::class, 'anexo'])->name('anexo');
        
        // Rota protegida por Spatie para atualizar status
        Route::patch('/ticket/{ticket}/status', [SuporteController::class, 'updateStatus'])
            ->name('updateStatus')
            ->middleware('role:ADMIN');
    });

    // Novas rotas Livewire Migradas
    Route::get('/dashboard-gerencial', Dashboard::class)->name('dashboard.gerencial');
    Route::get('/lancamentos-avancado', LancamentosAvancado::class)->name('lancamentos.avancado');

    Route::get('/configuracoes', [HomeController::class, 'configuracoes'])->name('configuracoes');
    Route::post('/configuracoes/testar-feriados-api', [\App\Http\Controllers\ConfiguracaoController::class, 'testarFeriadosApi'])->name('configuracoes.testar_feriados_api');

    // API de Calendário (session-based auth — browser fetch usa cookie, não Bearer Token)
    Route::get('/api/calendario/status', [CalendarioApiController::class, 'status'])->name('calendario.status');
    // Notificações
    Route::post('/notificacoes/marcar-lidas', [NotificacaoController::class, 'marcarTodasLidas'])->name('notificacoes.marcar_lidas');
    Route::post('/notificacoes/{id}/responder', [NotificacaoController::class, 'responder'])->name('notificacoes.responder');
    Route::post('/notificacoes/{id}/marcar-lida', [NotificacaoController::class, 'marcarLida'])->name('notificacoes.ler');

    // Apontamentos (CRUD e Ajustes)
    Route::prefix('apontamentos')->name('apontamentos.')->group(function () {
        Route::get('/novo', [ApontamentoController::class, 'create'])->name('create');
        Route::post('/', [ApontamentoController::class, 'store'])->name('store');
        Route::get('/{id}/editar', [ApontamentoController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ApontamentoController::class, 'update'])->name('update');
        Route::delete('/{id}', [ApontamentoController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/ajuste', [ApontamentoController::class, 'solicitarAjuste'])->name('solicitar_ajuste');
        Route::post('/{id}/aprovar-ajuste', [ApontamentoController::class, 'aprovarAjuste'])
            ->middleware('role:COORDENADOR|ADMIN') // Requer perfil de Coordenador ou Admin via Spatie
            ->name('aprovar_ajuste');
    });

    // Rotas de API consumidas pelo Frontend (AJAX do Cronômetro)
    Route::prefix('api/timer')->name('api.timer.')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\CronometroApiController::class, 'status'])->name('status');
        Route::post('/start', [\App\Http\Controllers\Api\CronometroApiController::class, 'iniciar'])->name('start');
        Route::post('/stop', [\App\Http\Controllers\Api\CronometroApiController::class, 'parar'])->name('stop');
    });

    // Atalhos AJAX para não quebrar a view (Selects dinâmicos)
    Route::get('/api/colaborador/{id}', [\App\Http\Controllers\Api\ColaboradorApiController::class, 'info']);
    Route::get('/api/centro-custo/{id}', [\App\Http\Controllers\Api\CentroCustoApiController::class, 'info']);

    // Histórico
    Route::get('/historico', [HistoricoController::class, 'index'])->name('historico.index');

    // Espelho de Ponto (Sólides)
    Route::get('/pontos', [PontoController::class, 'index'])->name('pontos.index');
    Route::post('/pontos/sincronizar-todos', [PontoController::class, 'sincronizarTodos'])->name('pontos.sincronizar_todos');

    // Aprovações (apenas Coordenadores, Admins e Gerenciais)
    Route::prefix('aprovacoes')->name('aprovacoes.')->middleware('role:COORDENADOR|ADMIN|GERENCIAL')->group(function () {
        Route::get('/', [AprovacaoController::class, 'dashboard'])->name('dashboard');
        Route::get('/{id}/analise', [AprovacaoController::class, 'analise'])->name('analise');
        Route::post('/{id}/processar', [AprovacaoController::class, 'processar'])->name('processar');
    });

    // Conformidade e Painel Administrativo (apenas Admins)
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/configuracoes/health', [\App\Http\Controllers\ConfiguracaoController::class, 'index'])->name('configuracoes.health');
        Route::post('/configuracoes/salvar', [\App\Http\Controllers\ConfiguracaoController::class, 'salvar'])->name('configuracoes.salvar');
        Route::post('/configuracoes/testar-solides-api', [\App\Http\Controllers\ConfiguracaoController::class, 'testarSolidesApi'])->name('configuracoes.testar_solides_api');
        Route::get('/configuracoes/status-whatsapp', [\App\Http\Controllers\ConfiguracaoController::class, 'statusWhatsapp'])->name('configuracoes.status_whatsapp');
        // Módulo de Gestão de Feriados e Localidades
        Route::prefix('feriados')->name('feriados.')->group(function () {
            Route::get('/', [\App\Http\Controllers\FeriadoController::class, 'index'])->name('index');
            Route::post('/sincronizar', [\App\Http\Controllers\FeriadoController::class, 'sincronizar'])->name('sincronizar');
            Route::post('/manual', [\App\Http\Controllers\FeriadoController::class, 'cadastrarManual'])->name('manual');
            Route::delete('/{id}', [\App\Http\Controllers\FeriadoController::class, 'deletar'])->name('deletar');
        });

        // Módulo de Conformidade
        Route::prefix('conformidade')->name('conformidade.')->group(function () {
            Route::get('/', [ConformidadeController::class, 'dashboard'])->name('dashboard');
            Route::post('/notificar-pendencias', [ConformidadeController::class, 'notificarPendencias'])->name('notificar_pendencias');
            Route::post('/enviar-aviso', [ConformidadeController::class, 'enviarAvisoPersonalizado'])->name('enviar_aviso');
            Route::post('/sincronizar-solides', [ConformidadeController::class, 'sincronizarSolides'])->name('sincronizar_solides');
        });

        // Módulo de Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'dashboard'])->name('index');
        });

        //Rotas de API de Feriados
        Route::get('/colaboradores/api/cidades', [\App\Http\Controllers\ColaboradorController::class, 'buscarCidades'])->name('api.cidades');
        Route::get('/colaboradores/api/buscar-nomes', [\App\Http\Controllers\ColaboradorController::class, 'buscarNomesAoVivo'])->name('api.buscar-nomes');

        // Módulo de Colaboradores (RH)
        Route::prefix('colaboradores')->name('colaboradores.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ColaboradorController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ColaboradorController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\ColaboradorController::class, 'update'])->name('update');
            Route::get('/{id}/historico', [\App\Http\Controllers\ColaboradorController::class, 'historico'])->name('historico');
        });

        // Módulo de Projetos / Obras
        Route::prefix('projetos')->name('projetos.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ProjetoController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ProjetoController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\ProjetoController::class, 'update'])->name('update');
            Route::post('/sincronizar-erp', [\App\Http\Controllers\ProjetoController::class, 'sincronizarErp'])->name('sincronizar');
        });

        // Módulo de Veículos
        Route::prefix('veiculos')->name('veiculos.')->group(function () {
            Route::get('/', [\App\Http\Controllers\VeiculoController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\VeiculoController::class, 'store'])->name('store');
            Route::put('/{veiculo}', [\App\Http\Controllers\VeiculoController::class, 'update'])->name('update');
            Route::post('/{veiculo}/toggle-status', [\App\Http\Controllers\VeiculoController::class, 'toggleStatus'])->name('toggleStatus');
        });

        // Módulo de Setores
        Route::prefix('setores')->name('setores.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SetorController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\SetorController::class, 'store'])->name('store');
            Route::put('/{setor}', [\App\Http\Controllers\SetorController::class, 'update'])->name('update');
            Route::post('/{setor}/toggle-status', [\App\Http\Controllers\SetorController::class, 'toggleStatus'])->name('toggleStatus');
        });

        Route::prefix('owner')->name('owner.')->group(function () {
            Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria');
            
            // WPPConnect Config
            Route::get('/wppconnect-config', [\App\Http\Controllers\WppConnectConfigController::class, 'index'])->name('wppconnect.index');
            Route::post('/wppconnect-config', [\App\Http\Controllers\WppConnectConfigController::class, 'store'])->name('wppconnect.store');
        });
    });

    // Automação WhatsApp
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/', [WhatsappController::class, 'index'])->name('index');
        Route::get('/status', [WhatsappController::class, 'statusSessao'])->name('status');
        Route::post('/iniciar-node', [WhatsappController::class, 'iniciarServidorNode'])->name('iniciar_node');
        Route::post('/stop', [WhatsappController::class, 'pararServidorNode'])->name('parar_node');
        Route::post('/teste', [WhatsappController::class, 'enviarTeste'])->name('enviar_teste');
    });

    // Profile (do Laravel Breeze, se mantido para o Colaborador/Usuário)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==========================================
// ROTAS DE DESENVOLVIMENTO (AUTO-LOGIN MÁGICO)
// ==========================================
if (app()->environment('local')) {
    Route::get('/dev/painel', function () {
        $users = \App\Models\User::with('colaborador')->get();
        
        $html = "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;'>";
        $html .= "<h1 style='color: #333;'>Painel de Testes Rápidos</h1>";
        $html .= "<p>Clique em um usuário abaixo para logar instantaneamente ignorando a senha:</p>";
        $html .= "<ul style='list-style: none; padding: 0;'>";
        
        foreach ($users as $user) {
            $cargo = $user->colaborador ? $user->colaborador->cargo : 'Sem perfil';
            $html .= "<li style='margin-bottom: 10px;'>
                        <a href='/dev/login/{$user->id}' style='display: block; padding: 15px; background: #f4f4f4; text-decoration: none; color: #333; border-radius: 5px; font-weight: bold;'>
                            🚪 Logar como: {$user->name} <br>
                            <small style='font-weight: normal; color: #666;'>{$user->email} | Cargo: {$cargo}</small>
                        </a>
                      </li>";
        }
        
        $html .= "</ul></div>";
        return $html;
    })->name('dev.login');

    Route::get('/dev/login/{id}', function ($id) {
        Auth::loginUsingId($id);
        return redirect()->route('painel');
    });

    Route::get('/teste-rls', function () {
        \Illuminate\Support\Facades\DB::beginTransaction();
        
        try {
            // 1. Pega o primeiro colaborador e o primeiro gestor reais do banco
            $operador = \App\Models\Colaborador::first(); 
            $gestor = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'gerente')->orWhere('name', 'GESTOR');
            })->first() ?? \App\Models\User::first(); // Fallback caso não ache
    
            // 2. Cria Cliente e Projeto Fake
            $cliente = \App\Models\CodigoCliente::create([
                'codigo' => '9999',
                'nome' => 'CLIENTE TESTE RLS',
                'ativo' => 1
            ]);
    
            $projeto = \App\Models\Projeto::create([
                'codigo' => 'R9999TST',
                'nome' => 'PROJETO TESTE RLS',
                'codigo_cliente_id' => $cliente->id,
                'ativo' => 1
            ]);
    
            // 3. Vincula o Gestor apenas ao Cliente (Testando a herança)
            if ($gestor->colaborador) {
                $gestor->colaborador->clientesGerenciados()->sync([$cliente->id]);
            }
    
            // 4. Cria o Apontamento do Operador no Projeto
            $apontamento = \App\Models\Apontamento::create([
                'colaborador_id' => $operador->id,
                'registrado_por_id' => $gestor->id,
                'projeto_id' => $projeto->id,
                'data_apontamento' => now()->toDateString(),
                'hora_inicio' => '08:00',
                'hora_termino' => '12:00',
                'local_execucao' => 'INT',
                'status_aprovacao' => 'EM_ANALISE',
                'contagem_edicao' => 0
            ]);
    
            // 5. Executa a Query Scope
            $apontamentosVisiveis = \App\Models\Apontamento::visibilidadePermitida($gestor)->get();
            $passou = $apontamentosVisiveis->contains('id', $apontamento->id);
    
            // 6. Prepara o Resultado
            $resultado = [
                'STATUS_DO_TESTE' => $passou ? 'SUCESSO: Gestor enxergou a obra via Cliente!' : 'FALHA: O escopo não funcionou.',
                'Apontamento_Criado_ID' => $apontamento->id,
                'Gestor_Testado' => $gestor->name,
                'Operador_Testado' => $operador->nome_completo,
                'Apontamentos_Retornados_Pela_Query' => $apontamentosVisiveis->pluck('id')
            ];
    
            // 7. Desfaz tudo para não sujar o banco
            \Illuminate\Support\Facades\DB::rollBack();
    
            return response()->json($resultado);
    
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'STATUS_DO_TESTE' => 'ERRO FATAL NA EXECUÇÃO',
                'MENSAGEM' => $e->getMessage(),
                'LINHA' => $e->getLine()
            ]);
        }
    });
}

