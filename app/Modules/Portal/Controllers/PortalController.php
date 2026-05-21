<?php

namespace App\Modules\Portal\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Modules\Pagos\Resources\PagoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
     * Devuelve los cursos de la sección donde el estudiante está matriculado.
     * Incluye seccion_curso_id (necesario para abrir el detalle del curso).
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
            ->whereNull('c.deleted_at')
            ->select([
                'sc.id as seccion_curso_id',
                'c.id',
                'c.nombre',
                'c.codigo',
                'c.horas_semana',
                'u.nombres as docente_nombres',
                'u.apellidos as docente_apellidos',
            ])
            ->orderBy('c.nombre')
            ->get();

        return response()->json(['data' => $cursos]);
    }

    /**
     * GET /api/v1/portal/mi-horario
     *
     * Devuelve los horarios de todos los cursos de la sección del estudiante,
     * organizados como una lista plana (frontend renderiza el grid).
     */
    public function miHorario(Request $request): JsonResponse
    {
        $estudianteId = $this->estudianteId($request);

        $seccionId = DB::table('matriculas')
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'activa')
            ->whereNull('deleted_at')
            ->value('seccion_id');

        if ($seccionId === null) {
            return response()->json(['data' => ['slots' => [], 'seccion' => null]]);
        }

        $seccion = DB::table('secciones as s')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->where('s.id', $seccionId)
            ->select([
                's.id', 's.nombre', 's.capacidad',
                'g.nombre as grado',
                'n.nombre as nivel',
            ])
            ->first();

        $slots = DB::table('horarios as h')
            ->join('seccion_curso as sc', 'sc.id', '=', 'h.seccion_curso_id')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->leftJoin('docentes as d', 'd.id', '=', 'sc.docente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('sc.seccion_id', $seccionId)
            ->whereNull('c.deleted_at')
            ->select([
                'h.id',
                'h.dia_semana',
                'h.hora_inicio',
                'h.hora_fin',
                'h.aula',
                'sc.id as seccion_curso_id',
                'c.id as curso_id',
                'c.nombre as curso',
                'c.codigo as curso_codigo',
                'u.nombres as docente_nombres',
                'u.apellidos as docente_apellidos',
            ])
            ->orderBy('h.dia_semana')
            ->orderBy('h.hora_inicio')
            ->get()
            ->map(fn ($s) => [
                'id'                => (int) $s->id,
                'dia_semana'        => (int) $s->dia_semana,
                'hora_inicio'       => substr($s->hora_inicio, 0, 5), // 'HH:MM'
                'hora_fin'          => substr($s->hora_fin, 0, 5),
                'aula'              => $s->aula,
                'seccion_curso_id'  => (int) $s->seccion_curso_id,
                'curso_id'          => (int) $s->curso_id,
                'curso'             => $s->curso,
                'curso_codigo'      => $s->curso_codigo,
                'docente'           => trim(($s->docente_nombres ?? '') . ' ' . ($s->docente_apellidos ?? '')) ?: null,
            ]);

        return response()->json([
            'data' => [
                'seccion' => $seccion,
                'slots'   => $slots,
            ],
        ]);
    }

    /**
     * GET /api/v1/portal/cursos/{seccionCursoId}
     *
     * Detalle de un curso para el estudiante: bimestres + semanas + contenido.
     * Valida que el curso pertenezca a la sección del estudiante autenticado.
     */
    public function detalleCurso(Request $request, int $seccionCursoId): JsonResponse
    {
        $estudianteId = $this->estudianteId($request);

        $matricula = DB::table('matriculas')
            ->where('estudiante_id', $estudianteId)
            ->where('estado', 'activa')
            ->whereNull('deleted_at')
            ->first();

        abort_if($matricula === null, 404, 'No tienes matrícula activa.');

        $asignacion = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->leftJoin('docentes as d', 'd.id', '=', 'sc.docente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('sc.id', $seccionCursoId)
            ->where('sc.seccion_id', $matricula->seccion_id)
            ->whereNull('c.deleted_at')
            ->select([
                'sc.id as seccion_curso_id',
                'c.id as curso_id', 'c.nombre as curso_nombre', 'c.codigo', 'c.horas_semana', 'c.descripcion',
                'u.nombres as docente_nombres', 'u.apellidos as docente_apellidos',
            ])
            ->first();

        abort_if($asignacion === null, 404, 'Este curso no está en tu sección.');

        // Horarios del curso
        $horarios = DB::table('horarios')
            ->where('seccion_curso_id', $seccionCursoId)
            ->orderBy('dia_semana')->orderBy('hora_inicio')
            ->get()
            ->map(fn ($h) => [
                'dia_semana'  => (int) $h->dia_semana,
                'hora_inicio' => substr($h->hora_inicio, 0, 5),
                'hora_fin'    => substr($h->hora_fin, 0, 5),
                'aula'        => $h->aula,
            ]);

        // Bimestres + semanas + contenido (solo del periodo activo)
        $bimestres = \App\Models\Bimestre::query()
            ->whereHas('periodo', fn ($q) => $q->where('estado', 'activo'))
            ->with(['semanas' => fn ($q) => $q->orderBy('numero')])
            ->orderBy('orden')
            ->get();

        // Cargar todo el contenido de este seccion_curso de una vez para evitar N+1
        $contenidosMap = \App\Models\ContenidoSemana::where('seccion_curso_id', $seccionCursoId)
            ->get()
            ->keyBy('semana_id');

        $materialesMap = \App\Models\MaterialSemana::where('seccion_curso_id', $seccionCursoId)
            ->orderBy('created_at')
            ->get()
            ->groupBy('semana_id');

        $tamanoLegible = function (int $b): string {
            if ($b < 1024) return $b . ' B';
            if ($b < 1024 * 1024) return round($b / 1024, 1) . ' KB';
            return round($b / 1024 / 1024, 1) . ' MB';
        };

        $bimestresOut = $bimestres->map(function ($b) use ($contenidosMap, $materialesMap, $tamanoLegible) {
            return [
                'id'           => (int) $b->id,
                'nombre'       => $b->nombre,
                'orden'        => (int) $b->orden,
                'fecha_inicio' => $b->fecha_inicio?->toDateString(),
                'fecha_fin'    => $b->fecha_fin?->toDateString(),
                'es_actual'    => $b->esActual(),
                'semanas'      => $b->semanas->map(function ($s) use ($contenidosMap, $materialesMap, $tamanoLegible) {
                    $c = $contenidosMap->get($s->id);
                    $mats = $materialesMap->get($s->id) ?? collect();
                    return [
                        'id'           => (int) $s->id,
                        'numero'       => (int) $s->numero,
                        'fecha_inicio' => $s->fecha_inicio?->toDateString(),
                        'fecha_fin'    => $s->fecha_fin?->toDateString(),
                        'es_actual'    => $s->esActual(),
                        'contenido'    => $c ? [
                            'titulo'       => $c->titulo,
                            'descripcion'  => $c->descripcion,
                            'recursos_url' => $c->recursos_url,
                            'tarea'        => $c->tarea,
                        ] : null,
                        'materiales'   => $mats->map(fn ($m) => [
                            'id'             => (int) $m->id,
                            'nombre'         => $m->nombre_original,
                            'url'            => $m->ruta ? asset('storage/' . $m->ruta) : null,
                            'tipo'           => $m->tipo,
                            'tamano'         => (int) $m->tamano,
                            'tamano_legible' => $tamanoLegible((int) $m->tamano),
                        ])->values()->all(),
                    ];
                })->all(),
            ];
        });

        return response()->json([
            'data' => [
                'curso' => [
                    'seccion_curso_id' => (int) $asignacion->seccion_curso_id,
                    'curso_id'         => (int) $asignacion->curso_id,
                    'nombre'           => $asignacion->curso_nombre,
                    'codigo'           => $asignacion->codigo,
                    'horas_semana'     => (int) $asignacion->horas_semana,
                    'descripcion'      => $asignacion->descripcion,
                    'docente'          => trim(($asignacion->docente_nombres ?? '') . ' ' . ($asignacion->docente_apellidos ?? '')) ?: null,
                ],
                'horarios'  => $horarios,
                'bimestres' => $bimestresOut,
            ],
        ]);
    }

    /**
     * POST /api/v1/portal/pagos/{pago}/subir-comprobante  (multipart)
     *
     * El apoderado/estudiante sube el voucher de un pago realizado por
     * Yape/Plin/transferencia. El pago queda en estado "pendiente" pero con
     * comprobante_url cargado; el admin lo verifica y marca como pagado.
     */
    public function subirComprobante(Request $request, Pago $pago): JsonResponse
    {
        $estudianteId = $this->estudianteId($request);

        // Verificar que el pago pertenece a una matrícula del estudiante autenticado.
        $matriculaPropia = DB::table('matriculas')
            ->where('id', $pago->matricula_id)
            ->where('estudiante_id', $estudianteId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $matriculaPropia) {
            abort(403, 'Este pago no te pertenece.');
        }

        if (! in_array($pago->estado, [Pago::ESTADO_PENDIENTE, Pago::ESTADO_VENCIDO], true)) {
            return response()->json([
                'message' => 'Solo puedes subir comprobante para pagos pendientes o vencidos.',
            ], 422);
        }

        $data = $request->validate([
            'metodo'        => ['required', Rule::in([
                Pago::METODO_TRANSFERENCIA,
                Pago::METODO_YAPE,
                Pago::METODO_PLIN,
                Pago::METODO_OTRO,
            ])],
            'comprobante'   => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:300'],
        ]);

        $path = $request->file('comprobante')->store('comprobantes', 'public');

        $pago->update([
            'comprobante_url' => $path,
            'metodo'          => $data['metodo'],
            'observaciones'   => trim(
                '[POR VERIFICAR] ' .
                ($data['observaciones'] ?? '') .
                ' (subido ' . now()->toDateTimeString() . ')'
            ),
        ]);

        return response()->json([
            'message' => 'Comprobante subido. El colegio verificará el pago en las próximas 24 horas.',
            'data'    => new PagoResource($pago->fresh()),
        ]);
    }

    private function estudianteId(Request $request): int
    {
        return (int) ($request->user()?->id ?? abort(401));
    }
}
