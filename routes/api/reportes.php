<?php

/*
| Rutas del módulo Reportes — Persona C
| Reportes simples del sistema.
*/

use App\Modules\Reportes\Controllers\ReporteAcademicoController;
use App\Modules\Reportes\Controllers\ReporteAdminController;
use App\Modules\Reportes\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reportes')->group(function () {
    Route::get('dashboard',              [ReporteController::class, 'dashboard']);
    Route::get('inscripciones',          [ReporteController::class, 'inscripciones']);
    Route::get('matriculas-por-seccion', [ReporteController::class, 'matriculasPorSeccion']);
    Route::get('pagos-por-periodo',      [ReporteController::class, 'pagosPorPeriodo']);
    Route::get('pdf',                    [ReporteController::class, 'pdf']);

    // Reportes académicos (BLOQUE 3B)
    Route::get('clases',      [ReporteAcademicoController::class, 'clases']);
    Route::get('alumnos',     [ReporteAcademicoController::class, 'alumnos']);
    Route::get('acta-notas',  [ReporteAcademicoController::class, 'actaNotas']);
    Route::get('desaprobados', [ReporteAcademicoController::class, 'desaprobados']);
    Route::get('boleta',      [ReporteAcademicoController::class, 'boleta']);
    Route::get('asistencia',  [ReporteAcademicoController::class, 'asistenciaReporte']);

    // Reportes administrativos (BLOQUE 4.3)
    Route::get('deuda',                   [ReporteAdminController::class, 'deuda']);
    Route::get('atrasados',               [ReporteAdminController::class, 'atrasados']);
    Route::get('recaudacion',             [ReporteAdminController::class, 'recaudacion']);
    Route::get('pagos-docentes-reporte',  [ReporteAdminController::class, 'pagosDocentesReporte']);
});
