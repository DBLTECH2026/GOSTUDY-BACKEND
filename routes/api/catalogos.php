<?php

/*
| Rutas del módulo Catálogos — Persona B
| Niveles, Grados, Secciones, Periodos Académicos.
*/

use Illuminate\Support\Facades\Route;
// use App\Modules\Catalogos\Controllers\NivelController;
// use App\Modules\Catalogos\Controllers\GradoController;
// use App\Modules\Catalogos\Controllers\SeccionController;
// use App\Modules\Catalogos\Controllers\PeriodoController;

Route::middleware('auth:sanctum')->group(function () {
    // Persona B: definir aquí las rutas de catálogos.
    // Route::apiResource('niveles', NivelController::class);
    // Route::apiResource('grados', GradoController::class);
    // Route::apiResource('secciones', SeccionController::class);
    // Route::apiResource('periodos', PeriodoController::class);
    // Route::post('periodos/{periodo}/activar', [PeriodoController::class, 'activar']);
});
