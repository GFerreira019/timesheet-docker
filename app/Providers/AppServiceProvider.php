<?php

namespace App\Providers;

use App\View\Composers\NotificacaoComposer;
use Illuminate\Support\Facades\View;
use App\Listeners\RegistrarFalhaLogin;
use App\Listeners\RegistrarLogin;
use App\Listeners\RegistrarLogout;
use App\Models\CentroCusto;
use App\Models\Colaborador;
use App\Models\Feriado;
use App\Models\Projeto;
use App\Observers\CentroCustoObserver;
use App\Observers\ColaboradorObserver;
use App\Observers\FeriadoObserver;
use App\Observers\ProjetoObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * Ponto central de registro de Observers e Listeners.
 *
 * Equivalente ao apps.py (ready()) + signals.py do Django:
 *
 * Django                                       → Laravel
 * ─────────────────────────────────────────────────────────────────────
 * @receiver([post_save, post_delete], Colaborador) → ColaboradorObserver
 * @receiver([post_save, post_delete], Projeto)     → ProjetoObserver
 * @receiver([post_save, post_delete], CentroCusto) → CentroCustoObserver
 * @receiver([post_save, post_delete], Feriado)     → FeriadoObserver
 * @receiver(user_logged_in)                        → RegistrarLogin
 * @receiver(user_logged_out)                       → RegistrarLogout
 * @receiver(user_login_failed)                     → RegistrarFalhaLogin
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Equivalente ao ready() do AppConfig do Django.
     */
    public function boot(): void
    {
        // ─────────────────────────────────────────────────────────────
        // OBSERVERS — Invalidação de Cache (equivalente aos signals Django)
        // ─────────────────────────────────────────────────────────────

        /**
         * Colaborador: invalida 'api_lista_auxiliares' ao criar/editar/excluir.
         * Django: limpar_cache_colaboradores (signals.py L74-80)
         */
        Colaborador::observe(ColaboradorObserver::class);

        /**
         * Projeto: invalida 'projeto_info_{id}' ao criar/editar/excluir.
         * Django: limpar_cache_projetos (signals.py L82-85)
         */
        Projeto::observe(ProjetoObserver::class);

        /**
         * CentroCusto: invalida 'cc_info_{id}' ao criar/editar/excluir.
         * Django: limpar_cache_centro_custo (signals.py L87-90)
         */
        CentroCusto::observe(CentroCustoObserver::class);

        /**
         * Feriado: invalida a chave de cache específica (data+cidade+uf).
         * Django: limpar_cache_feriados (signals.py L92-104)
         * Bônus Laravel: também invalida a chave ANTIGA se data/cidade/uf mudaram.
         */
        Feriado::observe(FeriadoObserver::class);

        // ─────────────────────────────────────────────────────────────
        // LISTENERS — Auditoria de Autenticação (equivalente aos signals Django)
        // ─────────────────────────────────────────────────────────────

        /**
         * Login bem-sucedido → LogAuditoria acao='LOGIN'
         * Django: log_login (signals.py L16-31) via user_logged_in
         */
        Event::listen(Login::class, RegistrarLogin::class);

        /**
         * Logout → LogAuditoria acao='LOGOUT'
         * Django: log_logout (signals.py L33-46) via user_logged_out
         */
        Event::listen(Logout::class, RegistrarLogout::class);

        /**
         * Falha de login → LogAuditoria acao='LOGIN_FALHA', user_id=null
         * Django: log_login_failed (signals.py L48-67) via user_login_failed
         */
        Event::listen(Failed::class, RegistrarFalhaLogin::class);

        // ─────────────────────────────────────────────────────────────
        // VIEW COMPOSER — Notificações Globais
        // ─────────────────────────────────────────────────────────────

        /**
         * Injeta $notificacoes_usuario, $notificacoes_nao_lidas_count e $is_owner_view
         * em TODOS os templates Blade.
         *
         * Equivalente ao context_processor do Django:
         *   notificacoes_globais(request) em context_processors.py
         *   Registrado em settings.py: TEMPLATES[0]['OPTIONS']['context_processors']
         */
        View::composer('*', NotificacaoComposer::class);


        // Configurar locale para pt_BR
        if (env('APP_ENV') !== 'testing') {
            \Carbon\Carbon::setLocale('pt_BR');
            \Carbon\CarbonInterval::setLocale('pt_BR');
        }
    }
}
