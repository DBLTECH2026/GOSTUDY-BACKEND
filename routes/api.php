<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — GOSTUDY
|--------------------------------------------------------------------------
|
| ⚠️  NO MODIFICAR ESTE ARCHIVO directamente. Cada módulo tiene su archivo
| en routes/api/<modulo>.php. Si necesitas registrar rutas, edita el archivo
| de TU módulo. Si necesitas añadir un módulo nuevo, avisa al equipo.
|
| Persona A: routes/api/auth.php, routes/api/personas.php
| Persona B: routes/api/catalogos.php, routes/api/matricula.php, routes/api/inscripcion.php
| Persona C: routes/api/pagos.php, routes/api/reportes.php, routes/api/portal.php
|
*/

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/personas.php';
    require __DIR__ . '/api/inscripcion.php';
    require __DIR__ . '/api/catalogos.php';
    require __DIR__ . '/api/matricula.php';
    require __DIR__ . '/api/pagos.php';
    require __DIR__ . '/api/portal.php';
    require __DIR__ . '/api/reportes.php';
});
