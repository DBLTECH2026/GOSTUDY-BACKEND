<?php

/*
| Rutas del módulo Matrícula — stub implementado por Persona C
| (cuando Persona B termine su módulo formal, debe consolidarse).
*/

use App\Modules\Matricula\Controllers\MatriculaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('matriculas',          [MatriculaController::class, 'index']);
    Route::post('matriculas',         [MatriculaController::class, 'store']);
    Route::get('matriculas/catalogo', [MatriculaController::class, 'catalogo']);
});
