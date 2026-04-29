<?php

/*
| Rutas del módulo Matrícula — Persona B
| Proceso de matrícula y ficha administrativa.
*/

use Illuminate\Support\Facades\Route;
// use App\Modules\Matricula\Controllers\MatriculaController;
// use App\Modules\Matricula\Controllers\FichaMatriculaController;

Route::middleware('auth:sanctum')->group(function () {
    // Persona B:
    // Route::apiResource('matriculas', MatriculaController::class);
    // Route::post('matriculas/{matricula}/aprobar', [MatriculaController::class, 'aprobar']);
    // Route::post('matriculas/{matricula}/retirar', [MatriculaController::class, 'retirar']);
    // Route::post('matriculas/{matricula}/ficha', [FichaMatriculaController::class, 'store']);
});
