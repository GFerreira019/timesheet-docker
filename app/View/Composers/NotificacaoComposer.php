<?php

namespace App\View\Composers;

use App\Models\Notificacao;
use Illuminate\View\View;

/**
 * NotificacaoComposer
 *
 * Equivalente ao context_processor Django:
 *   notificacoes_globais(request) do context_processors.py
 *
 * Injetado automaticamente em TODOS os templates Blade via AppServiceProvider.
 * Disponibiliza as variáveis globais de notificações no layout principal.
 *
 * Variáveis injetadas:
 *   $notificacoes_usuario        → coleção das últimas notificações
 *   $notificacoes_nao_lidas_count→ integer com badge do sino
 *   $is_owner_view               → bool — true = visão de respostas do owner
 *
 * Lógica RBAC (equivalente ao Django):
 *   Owner (is_superuser) → vê as RESPOSTAS dos colaboradores (últimas 15)
 *   Colaborador          → vê suas próprias notificações (últimas 10), badge = não lidas
 *   Não autenticado      → sem variáveis
 */
class NotificacaoComposer
{
    /**
     * Vincula dados de notificações ao template.
     * Equivalente ao retorno do context_processor do Django.
     */
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (!$user) {
            // Sem autenticação — equivalente ao `if not request.user.is_authenticated: return {}`
            $view->with([
                'notificacoes_usuario'         => collect(),
                'notificacoes_nao_lidas_count' => 0,
                'is_owner_view'                => false,
            ]);
            return;
        }

        // ── LÓGICA DE PRIVACIDADE: Toda notificação é estritamente pessoal ────────
        // Independentemente de ser admin/owner ou usuário comum, o sino
        // carrega apenas os alertas direcionados ao colaborador vinculado.
        $colaborador = $user->colaborador;

        if (!$colaborador) {
            $view->with([
                'notificacoes_usuario'         => collect(),
                'notificacoes_nao_lidas_count' => 0,
                'is_owner_view'                => false,
            ]);
            return;
        }

        // Últimas 10 notificações NÃO LIDAS (libera espaço no modal para novas ao invés de exibir antigas)
        $ultimas = Notificacao::where('colaborador_id', $colaborador->id)
            ->naoLidas()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Badge do sino = apenas as não lidas (equivalente ao .filter(lida=False).count())
        $count = Notificacao::where('colaborador_id', $colaborador->id)
            ->where('lida', false)
            ->count();

        $view->with([
            'notificacoes_usuario'         => $ultimas,
            'notificacoes_nao_lidas_count' => $count,
            'is_owner_view'                => false,
        ]);
    }
}
