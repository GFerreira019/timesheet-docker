<?php

namespace App\Console\Commands;

use App\Models\Apontamento;
use App\Models\Notificacao;
use Illuminate\Console\Command;

class NotifyPendingApprovals extends Command
{
    protected $signature = 'app:notify-pending-approvals';
    protected $description = 'Notifica gestores sobre apontamentos pendentes de aprovação na segunda manhã após a finalização';

    public function handle()
    {
        $this->info('Buscando apontamentos pendentes de aprovação...');

        // Otimização: Buscar a lista de Administradores fora do loop para poupar consultas
        $admins = \App\Models\Colaborador::whereHas('user', function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'ADMIN');
            });
        })->get();

        // O eager loading carrega o colaborador titular, os gestores do projeto e seus setores vinculados.
        $apontamentos = Apontamento::with(['colaborador', 'projeto.gestores.setoresVinculados'])
            ->where('status_aprovacao', 'EM_ANALISE')
            ->whereNotNull('hora_termino')
            ->whereDate('updated_at', '<=', now()->subDays(2)->toDateString())
            ->get();

        $count = 0;

        foreach ($apontamentos as $apontamento) {
            if ($apontamento->projeto && $apontamento->projeto->gestores->isNotEmpty()) {
                
                // Calcula a idade da pendência (em dias) a partir da meia-noite
                $diasPendentes = clone $apontamento->updated_at;
                $diasPendentes = $diasPendentes->startOfDay()->diffInDays(now()->startOfDay());

                $primeiroNome = 'Colaborador';
                if ($apontamento->colaborador && $apontamento->colaborador->nome_completo) {
                    $primeiroNome = explode(' ', $apontamento->colaborador->nome_completo)[0];
                }

                $dataApontamento = $apontamento->data_apontamento ? $apontamento->data_apontamento->format('d/m/Y') : 'Data não informada';

                // D+2: Notificar Coordenadores
                if ($diasPendentes == 2) {
                    foreach ($apontamento->projeto->gestores as $gestorColaborador) {
                        Notificacao::create([
                            'colaborador_id'  => $gestorColaborador->id,
                            'titulo'          => 'Aprovação Pendente',
                            'mensagem'        => "O apontamento de {$primeiroNome} em {$dataApontamento} está aguardando sua aprovação.",
                            'tipo'            => 'ALERTA',
                            'lida'            => false,
                            'apontamento_id'  => $apontamento->id,
                            'data_referencia' => $apontamento->data_apontamento,
                        ]);
                        $count++;
                    }
                } 
                // D+3: Escalonamento para Gerentes
                elseif ($diasPendentes == 3) {
                    // Descobre os setores de todos os Coordenadores vinculados a este projeto
                    $setoresIds = collect();
                    foreach ($apontamento->projeto->gestores as $coordenador) {
                        if ($coordenador->setoresVinculados) {
                            $setoresIds = $setoresIds->merge($coordenador->setoresVinculados->pluck('id'));
                        }
                    }
                    $setoresIds = $setoresIds->unique();

                    if ($setoresIds->isNotEmpty()) {
                        // Busca gerentes desses setores pela tabela explícita de gestão
                        $gerentes = \App\Models\Colaborador::whereHas('setoresGerenciados', function ($query) use ($setoresIds) {
                            $query->whereIn('colaborador_setor_gerenciado.setor_id', $setoresIds);
                        })->get();

                        foreach ($gerentes as $gerente) {
                            Notificacao::create([
                                'colaborador_id'  => $gerente->id,
                                'titulo'          => 'Aprovação Pendente',
                                'mensagem'        => "Aviso: O apontamento de {$apontamento->colaborador->nome_completo} na data {$dataApontamento} está pendente de aprovação pelo Coordenador responsável há mais de 48h.",
                                'tipo'            => 'ALERTA',
                                'lida'            => false,
                                'apontamento_id'  => $apontamento->id,
                                'data_referencia' => $apontamento->data_apontamento,
                            ]);
                            $count++;
                        }
                    }
                }
                // D+4+: Escalonamento Crítico (ADMIN)
                elseif ($diasPendentes >= 4) {
                    foreach ($admins as $admin) {
                        Notificacao::create([
                            'colaborador_id'  => $admin->id,
                            'titulo'          => 'Escalonamento Crítico: Aprovação Pendente',
                            'mensagem'        => "Alerta: O apontamento de {$apontamento->colaborador->nome_completo} em ({$dataApontamento}) está pendente há mais de 3 dias. A cadeia de gestão (Coordenador/Gerente) não resolveu a pendência.",
                            'tipo'            => 'ALERTA',
                            'lida'            => false,
                            'apontamento_id'  => $apontamento->id,
                            'data_referencia' => $apontamento->data_apontamento,
                        ]);
                        $count++;
                    }
                }
            }
        }

        $this->info("Concluído! Foram geradas {$count} notificações com sucesso.");
    }
}
