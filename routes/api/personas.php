<?php

/*
| Rutas del módulo Personas — stubs implementados por Persona C
| (cuando Persona A termine su módulo formal, debe consolidarse).
*/

use App\Modules\Personas\Controllers\ConsultaController;
use App\Modules\Personas\Controllers\DocenteController;
use App\Modules\Personas\Controllers\EstudianteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Consulta de identidad vía APIsPERU (proxy seguro, token en backend)
    Route::get('consulta/dni/{dni}', [ConsultaController::class, 'dni']);
    Route::get('consulta/ruc/{ruc}', [ConsultaController::class, 'ruc']);

    Route::get('estudiantes',  [EstudianteController::class, 'index']);
    Route::post('estudiantes', [EstudianteController::class, 'store']);

    Route::get('docentes',                [DocenteController::class, 'index']);
    Route::post('docentes',               [DocenteController::class, 'store']);
    Route::get('docentes/{docente}',      [DocenteController::class, 'show']);
    Route::put('docentes/{docente}',      [DocenteController::class, 'update']);
    Route::delete('docentes/{docente}',   [DocenteController::class, 'destroy']);
});
