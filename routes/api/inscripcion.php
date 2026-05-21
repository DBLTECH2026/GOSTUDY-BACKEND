<?php

/*
| Rutas del módulo Inscripción — Persona B
*/

use App\Modules\Inscripcion\Controllers\InscripcionController;
use Illuminate\Support\Facades\Route;

// Pública (sin auth) — formulario de inscripción del padre desde la web
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/inscripciones',                  [InscripcionController::class, 'store']);
    Route::post('/inscripcion/enviar-facturacion', [InscripcionController::class, 'enviarFacturacion']);
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/inscripcion/verificar-dni', [InscripcionController::class, 'verificarDni']);
});

// Admin (con auth Sanctum) — gestión de inscripciones pendientes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/inscripciones',                          [InscripcionController::class, 'index']);
    Route::get('/inscripciones/{inscripcion}',            [InscripcionController::class, 'show']);
    Route::post('/inscripciones/{inscripcion}/aprobar',   [InscripcionController::class, 'aprobar']);
    Route::post('/inscripciones/{inscripcion}/rechazar',  [InscripcionController::class, 'rechazar']);
});
