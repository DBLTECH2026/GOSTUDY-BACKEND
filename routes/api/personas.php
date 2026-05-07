<?php

/*
| Rutas del módulo Personas — Persona A
| Estudiantes, Docentes, Perfiles Familiares (Padres/Apoderados).
| Todas las rutas deben estar protegidas con auth:sanctum.
*/

use Illuminate\Support\Facades\Route;
// use App\Modules\Personas\Controllers\EstudianteController;
// use App\Modules\Personas\Controllers\DocenteController;
// use App\Modules\Personas\Controllers\PerfilFamiliarController;

Route::middleware('auth:sanctum')->group(function () {
    // Persona A: definir aquí las rutas de personas.
    // Route::apiResource('estudiantes', EstudianteController::class);
    // Route::apiResource('docentes', DocenteController::class);
    // Route::apiResource('perfiles-familiares', PerfilFamiliarController::class);
});
