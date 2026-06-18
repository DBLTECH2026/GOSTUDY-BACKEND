<?php

/*
| Rutas del módulo Académico — gestión del pivot seccion_curso
| (qué docente dicta qué curso en cada sección).
*/

use App\Modules\Academico\Controllers\AsignacionController;
use App\Modules\Academico\Controllers\CompetenciaController;
use App\Modules\Academico\Controllers\CursoController;
use App\Modules\Academico\Controllers\DocenteAcademicoController;
use App\Modules\Academico\Controllers\HorarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Listado de secciones (selector)
    Route::get('secciones', [AsignacionController::class, 'indexSecciones']);

    // Asignaciones curso↔docente de una sección
    Route::get('secciones/{seccion}/asignaciones', [AsignacionController::class, 'showAsignaciones']);
    Route::put('secciones/{seccion}/asignaciones', [AsignacionController::class, 'updateAsignaciones']);

    // Tutor de sección
    Route::put('secciones/{seccion}/tutor', [AsignacionController::class, 'updateTutor']);

    // Horarios de sección (admin)
    Route::get('secciones/{seccion}/horarios', [HorarioController::class, 'show']);
    Route::put('secciones/{seccion}/horarios', [HorarioController::class, 'update']);

    // CRUD cursos
    Route::get('cursos',          [CursoController::class, 'index']);
    Route::post('cursos',         [CursoController::class, 'store']);
    Route::get('cursos/{curso}',  [CursoController::class, 'show']);
    Route::put('cursos/{curso}',  [CursoController::class, 'update']);
    Route::delete('cursos/{curso}', [CursoController::class, 'destroy']);

    // Competencias por curso
    Route::get('cursos/{curso}/competencias',  [CompetenciaController::class, 'index']);
    Route::post('cursos/{curso}/competencias', [CompetenciaController::class, 'store']);
    Route::put('competencias/{competencia}',    [CompetenciaController::class, 'update']);
    Route::delete('competencias/{competencia}', [CompetenciaController::class, 'destroy']);

    // Panel del docente
    Route::prefix('docente')->group(function () {
        Route::get('mi-horario', [DocenteAcademicoController::class, 'miHorario']);
        Route::get('mis-pagos', [DocenteAcademicoController::class, 'misPagos']);
        Route::get('mis-clases', [DocenteAcademicoController::class, 'misClases']);
        Route::get('mis-clases/{seccionCursoId}', [DocenteAcademicoController::class, 'detalleClase'])
            ->whereNumber('seccionCursoId');
        Route::put('mis-clases/{seccionCursoId}/semanas/{semanaId}',
            [DocenteAcademicoController::class, 'actualizarContenido'])
            ->whereNumber('seccionCursoId')
            ->whereNumber('semanaId');

        // Materiales (archivos) por semana
        Route::post('mis-clases/{seccionCursoId}/semanas/{semanaId}/materiales',
            [DocenteAcademicoController::class, 'subirMaterial'])
            ->whereNumber('seccionCursoId')
            ->whereNumber('semanaId');
        Route::delete('mis-clases/{seccionCursoId}/semanas/{semanaId}/materiales/{materialId}',
            [DocenteAcademicoController::class, 'eliminarMaterial'])
            ->whereNumber('seccionCursoId')
            ->whereNumber('semanaId')
            ->whereNumber('materialId');

        // Alumnos / Notas / Asistencia de la clase
        Route::get('mis-clases/{seccionCursoId}/alumnos',
            [DocenteAcademicoController::class, 'alumnos'])
            ->whereNumber('seccionCursoId');
        Route::get('mis-clases/{seccionCursoId}/notas',
            [DocenteAcademicoController::class, 'notas'])
            ->whereNumber('seccionCursoId');
        Route::put('mis-clases/{seccionCursoId}/notas',
            [DocenteAcademicoController::class, 'guardarNotas'])
            ->whereNumber('seccionCursoId');
        Route::get('mis-clases/{seccionCursoId}/asistencia',
            [DocenteAcademicoController::class, 'asistencia'])
            ->whereNumber('seccionCursoId');
        Route::put('mis-clases/{seccionCursoId}/asistencia',
            [DocenteAcademicoController::class, 'guardarAsistencia'])
            ->whereNumber('seccionCursoId');
    });
});
