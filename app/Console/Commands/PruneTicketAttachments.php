<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PruneTicketAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:prune-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apaga fisicamente os anexos de tickets criados há mais de 90 dias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando rotina de expurgo de anexos de tickets...');

        // Busca tickets com mais de 90 dias e que possuem anexo_path preenchido
        $tickets = Ticket::whereNotNull('anexo_path')
            ->where('created_at', '<', Carbon::now()->subDays(90))
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('Nenhum anexo expirado encontrado para expurgo.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($tickets as $ticket) {
            // Verifica e deleta o arquivo físico do disco local
            if (Storage::disk('local')->exists($ticket->anexo_path)) {
                Storage::disk('local')->delete($ticket->anexo_path);
            }

            // Atualiza o registro removendo o caminho do anexo
            $ticket->update(['anexo_path' => null]);
            
            $count++;
        }

        $message = "Expurgo concluído. Foram apagados {$count} anexos de tickets antigos.";
        
        $this->info($message);
        Log::info("PruneTicketAttachments: {$message}");

        return Command::SUCCESS;
    }
}
