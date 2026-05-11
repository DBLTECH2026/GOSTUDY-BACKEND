<?php

/*
| Rutas del módulo Reportes — Persona C
| Reportes simples del sistema.
*/

use App\Modules\Reportes\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reportes')->group(function () {
    Route::get('dashboard',              [ReporteController::class, 'dashboard']);
    Route::get('inscripciones',          [ReporteController::class, 'inscripciones']);
    Route::get('matriculas-por-seccion', [ReporteController::class, 'matriculasPorSeccion']);
    Route::get('pagos-por-periodo',      [ReporteController::class, 'pagosPorPeriodo']);
});
