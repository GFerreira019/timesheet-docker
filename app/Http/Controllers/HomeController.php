<?php

namespace App\Http\Controllers;

use App\Helpers\AcessoHelper;
use App\Models\Apontamento;
use App\Models\Colaborador;
use App\Models\Notificacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HomeController
 *
 * Equivalente às views Django:
 *   home_redirect_view()   → redirect()
 *   home_view()            → home()
 *   configuracoes_view()   → configuracoes()
 *   marcar_todas_lidas_view() → marcarTodasLidas()
 */
class HomeController extends Controller
{
    /**
     * Redireciona / para /home.
     * Equivalente ao home_redirect_view() do Django.
     */
    public function redirect(): RedirectResponse
    {
        return redirect()->route('home');
    }

    /**
     * Tela principal do sistema (Hub de navegação).
     * Equivalente ao home_view() do Django.
     *
     * GET /home
     */
    public function index(): View
    {
        $user  = auth()->user();
        $colab = $user?->colaborador;

        // Contagem de notificações não lidas para o badge do sino
        $qtdNotificacoesNaoLidas = 0;
        if ($colab) {
            $qtdNotificacoesNaoLidas = Notificacao::where('colaborador_id', $colab->id)
                ->where('lida', false)
                ->count();
        }

        // Verifica se há check-in aberto (atividade em andamento)
        $atividadeEmAndamento = false;
        if ($colab) {
            $atividadeEmAndamento = Apontamento::where('colaborador_id', $colab->id)
                ->whereNull('hora_termino')
                ->exists();
        }

        return view('home', [
            'titulo'                    => 'Menu Principal',
            'is_gestor'                 => AcessoHelper::isGerente($user),
            'is_owner'                  => AcessoHelper::isOwner($user),
            'pode_ratear'               => AcessoHelper::podeFazerRateio($user),
            'qtd_notificacoes'          => $qtdNotificacoesNaoLidas,
            'atividade_em_andamento'    => $atividadeEmAndamento,
        ]);
    }

    /**
     * Tela de configurações do usuário.
     * Equivalente ao configuracoes_view() do Django.
     *
     * GET /configuracoes
     */
    public function configuracoes(): View
    {
        $user  = auth()->user();
        $colab = $user?->colaborador;

        return view('configuracoes', [
            'titulo'               => 'Configurações do Usuário',
            'change_password_url'  => route('password.update'),
            'colaborador'          => $colab,
        ]);
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     * Equivalente ao marcar_todas_lidas_view() do Django.
     *
     * POST /notificacoes/marcar-lidas
     */
    public function marcarTodasLidas(Request $request): RedirectResponse
    {
        $user  = auth()->user();
        $colab = $user?->colaborador;

        if ($colab) {
            // Equivalente ao Django: Notificacao.objects.filter(colaborador=colab, lida=False).update(lida=True)
            Notificacao::where('colaborador_id', $colab->id)
                ->where('lida', false)
                ->update(['lida' => true]);

            session()->flash('success', 'Notificações marcadas como lidas.');
        }

        $referer = $request->header('referer');
        return $referer ? redirect($referer) : redirect()->route('home');
    }

    /**
     * Salva a resposta do colaborador em uma notificação.
     * Equivalente ao responder_notificacao_view() do Django.
     *
     * POST /notificacoes/{id}/responder
     */
    public function responderNotificacao(Request $request, int $id): RedirectResponse
    {
        $notif = Notificacao::findOrFail($id);
        $user  = auth()->user();
        $colab = $user?->colaborador;

        // Segurança: só o colaborador vinculado pode responder
        if ($colab && $notif->colaborador_id !== $colab->id) {
            session()->flash('error', 'Acesso negado.');
            return redirect()->route('home');
        }

        $resposta = $request->input('resposta_texto');
        if ($resposta) {
            $notif->comentario_colaborador = $resposta;
            $notif->lida = true;
            $notif->save();
            session()->flash('success', 'Resposta enviada ao gestor.');
        }

        $referer = $request->header('referer');
        return $referer ? redirect($referer) : redirect()->route('home');
    }
}
