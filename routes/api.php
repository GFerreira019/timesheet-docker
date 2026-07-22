<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjetoApiController;
use App\Http\Controllers\Api\CentroCustoApiController;
use App\Http\Controllers\Api\ColaboradorApiController;
use App\Http\Controllers\Api\CalendarioApiController;
use App\Http\Controllers\Api\CronometroApiController;
use App\Http\Controllers\Api\DashboardApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Endpoint de Health Check público
Route::get('/health', [DashboardApiController::class, 'health'])->name('api.health');

Route::middleware('auth:sanctum')->group(function () {

    // Projetos
    Route::get('/projetos/{id}/info', [ProjetoApiController::class, 'info'])->name('api.projetos.info');

    // Centros de Custo
    Route::get('/centros-custo/{id}/info', [CentroCustoApiController::class, 'info'])->name('api.centros_custo.info');

    // Colaboradores
    Route::get('/colaboradores/auxiliares', [ColaboradorApiController::class, 'auxiliares'])->name('api.colaboradores.auxiliares');
    Route::get('/colaboradores/{id}/info', [ColaboradorApiController::class, 'info'])->name('api.colaboradores.info');

    // Calendário
    Route::get('/calendario/status', [CalendarioApiController::class, 'status'])->name('api.calendario.status');

    // Cronômetro (AJAX via app)
    Route::prefix('cronometro')->name('api.cronometro.')->group(function () {
        Route::get('/status', [CronometroApiController::class, 'status'])->name('status');
        Route::post('/iniciar', [CronometroApiController::class, 'iniciar'])->name('iniciar');
        Route::post('/parar', [CronometroApiController::class, 'parar'])->name('parar');
    });

    // Dashboard Data e Relatórios
    Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/data', [DashboardApiController::class, 'data'])->name('data');
        Route::get('/exportar', [DashboardApiController::class, 'exportarJson'])->name('exportar');
    });

});
