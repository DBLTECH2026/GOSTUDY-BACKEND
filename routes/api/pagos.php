<?php

/*
| Rutas del módulo Pagos — Persona C
| Registrar pagos, anular, estado de cuenta.
*/

use App\Modules\Pagos\Controllers\EstadoCuentaController;
use App\Modules\Pagos\Controllers\PagoController;
use App\Modules\Pagos\Controllers\PagoDocenteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('pagos', [PagoController::class, 'index']);
    Route::post('pagos/{pago}/registrar', [PagoController::class, 'registrar']);
    Route::post('pagos/{pago}/anular', [PagoController::class, 'anular']);

    Route::get('estudiantes/{estudiante}/estado-cuenta', [EstadoCuentaController::class, 'show']);

    // Pagos a docentes (admin)
    Route::get('pagos-docentes/docentes', [PagoDocenteController::class, 'docentes']);
    Route::get('pagos-docentes', [PagoDocenteController::class, 'index']);
    Route::post('pagos-docentes', [PagoDocenteController::class, 'store']);
    Route::put('pagos-docentes/{pagoDocente}', [PagoDocenteController::class, 'update']);
    Route::put('pagos-docentes/{pagoDocente}/pagar', [PagoDocenteController::class, 'marcarPagado']);
    Route::delete('pagos-docentes/{pagoDocente}', [PagoDocenteController::class, 'destroy']);
});
