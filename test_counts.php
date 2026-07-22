<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dataRef = now()->toDateString();
$q1 = \App\Models\Colaborador::count();

$q2 = \App\Models\Colaborador::whereHas('user')->count();

$q3 = \App\Models\Colaborador::where(function ($query) {
    $query->whereNull('cargo')
          ->orWhere(function($sub) {
              $sub->where('cargo', 'NOT LIKE', '%DIRETOR%')
                  ->where('cargo', 'NOT LIKE', '%DIRETORIA%')
                  ->where('cargo', 'NOT LIKE', '%PRESIDENTE%')
                  ->where('cargo', 'NOT LIKE', '%ESTAGIÁRIO%')
                  ->where('cargo', 'NOT LIKE', '%ESTAGIARIO%');
          });
})->count();

$q4 = \App\Models\Colaborador::where(function($query) use ($dataRef) {
    $query->whereNull('created_at')->orWhereDate('created_at', '<=', $dataRef);
})->count();

print_r([
    '1. Total no Banco' => $q1,
    '2. Passaram pelo Filtro de User (sem is_active)' => $q2,
    '3. Passaram pelo Filtro de Cargo' => $q3,
    '4. Passaram pelo Filtro de Data' => $q4,
]);
