<?php

/*
| Rutas del módulo Reportes — Persona C
| Reportes simples del sistema.
*/

use Illuminate\Support\Facades\Route;
// use App\Modules\Reportes\Controllers\ReporteController;

Route::middleware('auth:sanctum')->prefix('reportes')->group(function () {
    // Persona C:
    // Route::get('matriculas-por-seccion', [ReporteController::class, 'matriculasPorSeccion']);
    // Route::get('pagos-por-periodo', [ReporteController::class, 'pagosPorPeriodo']);
});
