<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendamentos (Substitui o app/Console/Kernel.php do Laravel 10-)
Schedule::command('timesheet:aprovar-automatico')->dailyAt('00:00')->withoutOverlapping()->runInBackground()->sentryMonitor('timesheet-aprovar-automatico');
Schedule::command('timesheet:importar-feriados')->yearly()->withoutOverlapping()->runInBackground()->sentryMonitor('timesheet-importar-feriados');
// Schedule::command('erp:sync-obras')->dailyAt('01:00'); // Desativado temporariamente (manutenção manual)
Schedule::command('erp:sync-usuarios')->dailyAt('01:30')->withoutOverlapping()->runInBackground()->sentryMonitor('erp-sync-usuarios');

// Rotinas de Backup (Spatie)
Schedule::command('backup:run')->dailyAt('00:30')->withoutOverlapping()->runInBackground()->sentryMonitor('backup-run');
Schedule::command('backup:clean')->dailyAt('02:00')->withoutOverlapping()->runInBackground()->sentryMonitor('backup-clean');

// Database Archiving (Cold Storage)
Schedule::job(new \App\Jobs\ArchiveOldRecordsJob)->monthlyOn(1, '03:00')->withoutOverlapping()->sentryMonitor('archive-old-records'); // Jobs nativamente não precisam de runInBackground no scheduler, a fila lida com a assincronia

// Notificação de Apontamentos Pendentes de Aprovação (Dias úteis às 09:00)
Schedule::command('app:notify-pending-approvals')->dailyAt('09:00')->weekdays()->withoutOverlapping()->runInBackground()->sentryMonitor('app-notify-pending-approvals');

// Expurgo automático de anexos de tickets (1x por mês)
Schedule::command('tickets:prune-attachments')->monthly()->withoutOverlapping()->runInBackground()->sentryMonitor('prune-ticket-attachments');