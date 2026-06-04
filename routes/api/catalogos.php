<?php

/*
| Rutas del módulo Catálogos — Persona B
*/

use App\Modules\Catalogos\Controllers\CatalogoController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalogos')->group(function () {
    // Públicos: necesarios para el formulario de inscripción pública.
    Route::get('/niveles-grados', [CatalogoController::class, 'nivelesGrados']);
    Route::get('/periodo-activo', [CatalogoController::class, 'periodoActivo']);

    // Ubigeo RENIEC (cascada) — público para el formulario de inscripción.
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/ubigeo/departamentos', [CatalogoController::class, 'departamentos']);
        Route::get('/ubigeo/provincias',    [CatalogoController::class, 'provincias']);
        Route::get('/ubigeo/distritos',     [CatalogoController::class, 'distritos']);
    });
});
