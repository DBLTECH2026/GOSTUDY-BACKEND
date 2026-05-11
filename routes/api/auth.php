<?php

/*
| Rutas del módulo Auth — Persona A
*/

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Admin / Docente — email + password
    Route::post('/admin/register', [AuthController::class, 'registerAdmin']);
    Route::post('/admin/login',    [AuthController::class, 'loginAdmin']);

    // Estudiante — DNI + PIN
    Route::post('/portal/register', [AuthController::class, 'registerPortal']);
    Route::post('/portal/login',    [AuthController::class, 'loginPortal']);

    // Comunes (requieren token Bearer)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
