<?php

namespace App\Modules\Portal\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Modules\Pagos\Resources\PagoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * GOSTUDY — Persona C · Portal del Estudiante.
 *
 * Auth: el usuario autenticado debe ser un Estudiante (no admin/docente).
 * Persona A define el guard `portal` y el middleware EnsureIsEstudiante.
 * Hasta entonces estos métodos asumen que `$request->user()->id` corresponde
 * a `estudiantes.id`.
 */
class PortalController extends Controller
{
    /**
     * GET /api/v1/portal/mi-perfil
     */
    public function miPerfil(Request $request): JsonResponse
    {
        $estudianteId = $this->estudianteId($request);

        $estudiante = DB::table('estudiantes')
            ->where('id', $estudianteId)
            ->first();

        abort_if($estudiante === null, 404);

        $apoderados = DB::table('perfiles_familiares')
            ->where('estudiante_id', $estudianteId)
            ->get();

        return response()->json([
            'data' => [
                'estudiante' => $estudiante,
                'apoderados' => $apoderados,
            ],
        ]);
    }

    /**
     * GET /api/v1/portal/mi-matricula
     */
    public function miMatricula(Request $request): JsonResponse
    {
        $estudianteId = $this->estudianteId($request);

        $matricula = DB::table('matriculas as m')
            ->join('periodos_academicos as p', 'p.id', '=', 'm.periodo_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->leftJoin('docentes as d', 'd.id', '=', 's.docente_tutor_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('m.estudiante_id', $estudianteId)
            ->where('m.estado', 'activa')
            ->whereNull('m.deleted_at')
            ->select([
                'm.id',
                'm.fecha_matricula',
                'm.estado',
                'p.anio as periodo_anio',
                'p.descripcion as periodo_descripcion',
                'n.nombre as nivel',
                'g.nombre as grado',
                's.nombre as seccion',
                'u.nombres as tutor_nombres',
                'u.apellidos as tutor_apellidos',
            ])
            ->first();

        abort_if($matricula === null, 404, 'No tienes matrícula activa.');

        return response()->json(['data' => $matricula]);
    }

    /**
     * GET /api/v1/portal/mis-pagos
     */
    public function misPagos(Request $request): AnonymousResourceCollection
    {
        $estudianteId = $this->estudianteId($request);

        $matriculaIds = DB::table('matriculas')
            ->where('estudiante_id', $estudianteId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $pagos = Pago::whereIn('matricula_id', $matriculaIds)
            ->orderBy('fecha_vencimiento')
            ->get();

        return PagoResource::collection($pagos);
    }

    /**
     * GET /api/v1/portal/mis-cursos
     *
     * Devuelve los cursos de la sección donde el estudiante está matriculado
     * (sin notas — eso es v2).
     */
    public function misCursos(Request $request): JsonResponse
    {
        $estudianteId = $this->estudianteId($request);

        $seccionId = DB::table('matriculas')
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'activa')
            ->whereNull('deleted_at')
            ->value('seccion_id');

        if ($seccionId === null) {
            return response()->json(['data' => []]);
        }

        $cursos = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->leftJoin('docentes as d', 'd.id', '=', 'sc.docente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('sc.seccion_id', $seccionId)
            ->select([
                'c.id',
                'c.nombre',
                'c.codigo',
                'c.horas_semana',
                'u.nombres as docente_nombres',
                'u.apellidos as docente_apellidos',
            ])
            ->get();

        return response()->json(['data' => $cursos]);
    }

    private function estudianteId(Request $request): int
    {
        return (int) ($request->user()?->id ?? abort(401));
    }
}
