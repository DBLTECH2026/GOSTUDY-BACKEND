<?php

/*
| Rutas del módulo Pagos — Persona C
| Registrar pagos, anular, estado de cuenta.
*/

use Illuminate\Support\Facades\Route;
// use App\Modules\Pagos\Controllers\PagoController;
// use App\Modules\Pagos\Controllers\EstadoCuentaController;

Route::middleware('auth:sanctum')->group(function () {
    // Persona C:
    // Route::get('pagos', [PagoController::class, 'index']);
    // Route::post('pagos/{pago}/registrar', [PagoController::class, 'registrar']);
    // Route::post('pagos/{pago}/anular', [PagoController::class, 'anular']);
    // Route::get('estudiantes/{estudiante}/estado-cuenta', [EstadoCuentaController::class, 'show']);
});
