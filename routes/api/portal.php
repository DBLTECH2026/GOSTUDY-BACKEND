<?php

/*
| Rutas del Portal del Estudiante — Persona C
| Auth: guard 'sanctum' pero validando que el token pertenece a un Estudiante
| (no a un User admin/docente). Ver middleware EnsureIsEstudiante (Persona A).
*/

use App\Modules\Portal\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'/*, 'es.estudiante'*/])->prefix('portal')->group(function () {
    Route::get('/mi-perfil',    [PortalController::class, 'miPerfil']);
    Route::get('/mi-matricula', [PortalController::class, 'miMatricula']);
    Route::get('/mis-pagos',    [PortalController::class, 'misPagos']);
    Route::get('/mis-cursos',   [PortalController::class, 'misCursos']);
});
