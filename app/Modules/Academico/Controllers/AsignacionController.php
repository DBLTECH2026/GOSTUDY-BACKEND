<?php

namespace App\Modules\Academico\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Seccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Gestión del pivot seccion_curso:
 *   ┌──────────┐      ┌──────────────┐      ┌────────┐
 *   │ Seccion  │──────│ seccion_curso│──────│ Curso  │
 *   └──────────┘      └──────────────┘      └────────┘
 *                            │
 *                            ▼
 *                       ┌─────────┐
 *                       │ Docente │ (asignado a ese curso EN esa sección)
 *                       └─────────┘
 */
class AsignacionController extends Controller
{
    /**
     * GET /secciones — lista de secciones con metadatos para el selector.
     */
    public function indexSecciones(): JsonResponse
    {
        $secciones = Seccion::with(['grado.nivel', 'periodo', 'docenteTutor.usuario'])
            ->orderBy('periodo_id', 'desc')
            ->get()
            ->map(fn (Seccion $s) => [
                'id'              => (int) $s->id,
                'nombre'          => $s->nombre,
                'grado'           => $s->grado?->nombre,
                'nivel'           => $s->grado?->nivel?->nombre,
                'periodo'         => $s->periodo?->descripcion,
                'capacidad'       => (int) $s->capacidad,
                'matriculados'    => $s->matriculas()->where('estado', 'activa')->count(),
                'docente_tutor'   => $s->docenteTutor
                    ? trim(($s->docenteTutor->usuario?->nombres ?? '') . ' ' . ($s->docenteTutor->usuario?->apellidos ?? ''))
                    : null,
                'cursos_asignados' => $s->cursos()->count(),
                'cursos_total'    => Curso::where('grado_id', $s->grado_id)->count(),
                'label'           => "{$s->grado?->nombre} {$s->nombre} — {$s->grado?->nivel?->nombre} ({$s->periodo?->descripcion})",
            ]);

        return response()->json(['data' => $secciones]);
    }

    /**
     * GET /secciones/{id}/asignaciones — para una sección, devuelve TODOS los
     * cursos de su grado y, si están asignados, el docente correspondiente.
     */
    public function showAsignaciones(Seccion $seccion): JsonResponse
    {
        $seccion->load(['grado.nivel', 'periodo']);

        // Cursos del grado de la sección
        $cursos = Curso::where('grado_id', $seccion->grado_id)
            ->orderBy('nombre')
            ->get();

        // Mapa curso_id -> docente_id ya asignado
        $asignaciones = DB::table('seccion_curso')
            ->where('seccion_id', $seccion->id)
            ->pluck('docente_id', 'curso_id');

        $items = $cursos->map(fn (Curso $c) => [
            'curso_id'      => (int) $c->id,
            'curso_nombre'  => $c->nombre,
            'curso_codigo'  => $c->codigo,
            'horas_semana'  => (int) $c->horas_semana,
            'docente_id'    => isset($asignaciones[$c->id]) ? (int) $asignaciones[$c->id] : null,
            'asignado'      => $asignaciones->has($c->id),
        ]);

        // Lista de docentes disponibles (activos)
        $docentes = Docente::with('usuario')->get()
            ->filter(fn (Docente $d) => ($d->usuario?->estado ?? null) === 'activo')
            ->values()
            ->map(fn (Docente $d) => [
                'id'              => (int) $d->id,
                'codigo_docente'  => $d->codigo_docente,
                'nombre_completo' => trim(($d->usuario?->nombres ?? '') . ' ' . ($d->usuario?->apellidos ?? '')),
                'especialidad'    => $d->especialidad,
            ]);

        return response()->json([
            'data' => [
                'seccion' => [
                    'id'        => (int) $seccion->id,
                    'nombre'    => $seccion->nombre,
                    'grado'     => $seccion->grado?->nombre,
                    'nivel'     => $seccion->grado?->nivel?->nombre,
                    'periodo'   => $seccion->periodo?->descripcion,
                    'capacidad' => (int) $seccion->capacidad,
                    'label'     => "{$seccion->grado?->nombre} {$seccion->nombre} — {$seccion->grado?->nivel?->nombre}",
                ],
                'asignaciones' => $items,
                'docentes'     => $docentes,
            ],
        ]);
    }

    /**
     * PUT /secciones/{id}/asignaciones — actualización batch.
     * Payload: { asignaciones: [ { curso_id, docente_id|null }, ... ] }
     *
     * Implementación tipo "diff": preserva los IDs existentes en seccion_curso
     * para que NO se pierdan los registros hijos (horarios, contenido_semana,
     * materiales_semana) que dependen de ellos vía cascadeOnDelete.
     */
    public function updateAsignaciones(Request $request, Seccion $seccion): JsonResponse
    {
        $data = $request->validate([
            'asignaciones'              => ['required', 'array'],
            'asignaciones.*.curso_id'   => ['required', 'integer', Rule::exists('cursos', 'id')->where('grado_id', $seccion->grado_id)],
            'asignaciones.*.docente_id' => ['nullable', 'integer', Rule::exists('docentes', 'id')],
        ]);

        DB::transaction(function () use ($data, $seccion) {
            // Estado actual: [curso_id => docente_id|null]
            $actuales = DB::table('seccion_curso')
                ->where('seccion_id', $seccion->id)
                ->get(['curso_id', 'docente_id'])
                ->keyBy('curso_id');

            // Estado deseado: solo cursos CON docente (los null se interpretan como "quitar asignación")
            $deseado = [];
            foreach ($data['asignaciones'] as $a) {
                if (! empty($a['docente_id'])) {
                    $deseado[(int) $a['curso_id']] = (int) $a['docente_id'];
                }
            }

            $now = now();

            // 1) Cursos a ELIMINAR: estaban antes pero ya no se quieren
            //    (esto SÍ borra sus horarios/contenidos por cascade, lo cual es correcto
            //    porque el curso ya no se dicta en esta sección).
            $aEliminar = array_diff(array_keys($actuales->all()), array_keys($deseado));
            if (! empty($aEliminar)) {
                DB::table('seccion_curso')
                    ->where('seccion_id', $seccion->id)
                    ->whereIn('curso_id', $aEliminar)
                    ->delete();
            }

            // 2) Cursos a ACTUALIZAR o INSERTAR
            foreach ($deseado as $cursoId => $docenteNuevo) {
                if ($actuales->has($cursoId)) {
                    // Ya existe — solo UPDATE del docente (si cambió). Preserva el ID.
                    if ((int) $actuales[$cursoId]->docente_id !== $docenteNuevo) {
                        DB::table('seccion_curso')
                            ->where('seccion_id', $seccion->id)
                            ->where('curso_id', $cursoId)
                            ->update([
                                'docente_id' => $docenteNuevo,
                                'updated_at' => $now,
                            ]);
                    }
                } else {
                    // Nuevo — INSERT.
                    DB::table('seccion_curso')->insert([
                        'seccion_id' => $seccion->id,
                        'curso_id'   => $cursoId,
                        'docente_id' => $docenteNuevo,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Asignaciones actualizadas.',
            'data'    => [
                'asignadas' => collect($data['asignaciones'])->filter(fn ($a) => !empty($a['docente_id']))->count(),
                'total'     => count($data['asignaciones']),
            ],
        ]);
    }

    /**
     * PUT /secciones/{id}/tutor — asigna/quita docente tutor de la sección.
     */
    public function updateTutor(Request $request, Seccion $seccion): JsonResponse
    {
        $data = $request->validate([
            'docente_id' => ['nullable', 'integer', Rule::exists('docentes', 'id')],
        ]);

        $seccion->update(['docente_tutor_id' => $data['docente_id'] ?? null]);

        return response()->json([
            'message' => $data['docente_id'] ? 'Tutor asignado.' : 'Tutor removido.',
        ]);
    }
}
