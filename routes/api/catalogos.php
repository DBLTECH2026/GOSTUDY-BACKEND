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
});
