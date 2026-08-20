<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendamentos (Substitui o app/Console/Kernel.php do Laravel 10-)
Schedule::command('timesheet:aprovar-automatico')->dailyAt('00:00');
Schedule::command('timesheet:importar-feriados')->yearly();
Schedule::command('erp:sync-obras')->dailyAt('01:00');
Schedule::command('erp:sync-usuarios')->dailyAt('01:30');

// Rotinas de Backup (Spatie)
Schedule::command('backup:run')->dailyAt('00:30');
Schedule::command('backup:clean')->dailyAt('02:00');

// Database Archiving (Cold Storage)
Schedule::job(new \App\Jobs\ArchiveOldRecordsJob)->monthlyOn(1, '03:00');

// Notificação de Apontamentos Pendentes de Aprovação (Dias úteis às 08:00)
Schedule::command('app:notify-pending-approvals')->dailyAt('08:00')->weekdays();