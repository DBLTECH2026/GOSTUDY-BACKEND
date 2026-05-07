<?php

/*
| Rutas del módulo Inscripción — Persona B
| ⚠️ ENDPOINT PÚBLICO (sin auth) para que el padre inscriba al alumno desde la web.
| Aplicar throttle:5,1 (5 requests por minuto por IP).
*/

use Illuminate\Support\Facades\Route;
// use App\Modules\Inscripcion\Controllers\InscripcionPublicaController;
// use App\Modules\Inscripcion\Controllers\InscripcionAdminController;

// Públicas (sin auth, con rate limit)
Route::middleware('throttle:5,1')->group(function () {
    // Route::post('/inscripcion', [InscripcionPublicaController::class, 'store']);
    // Route::get('/inscripcion/{codigo}/estado', [InscripcionPublicaController::class, 'estado']);
});

// Admin (con auth)
Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/inscripciones', [InscripcionAdminController::class, 'index']);
    // Route::get('/inscripciones/{inscripcion}', [InscripcionAdminController::class, 'show']);
    // Route::post('/inscripciones/{inscripcion}/aprobar', [InscripcionAdminController::class, 'aprobar']);
    // Route::post('/inscripciones/{inscripcion}/rechazar', [InscripcionAdminController::class, 'rechazar']);
});
