<?php

/*
| Rutas del módulo Pagos — Persona C
| Registrar pagos, anular, estado de cuenta.
*/

use App\Modules\Pagos\Controllers\EstadoCuentaController;
use App\Modules\Pagos\Controllers\PagoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('pagos', [PagoController::class, 'index']);
    Route::post('pagos/{pago}/registrar', [PagoController::class, 'registrar']);
    Route::post('pagos/{pago}/anular', [PagoController::class, 'anular']);

    Route::get('estudiantes/{estudiante}/estado-cuenta', [EstadoCuentaController::class, 'show']);
});
