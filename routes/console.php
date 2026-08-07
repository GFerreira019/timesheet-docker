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
