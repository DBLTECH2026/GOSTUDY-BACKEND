<?php

namespace App\Modules\Academico\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Bimestre;
use App\Models\Calificacion;
use App\Models\Competencia;
use App\Models\ContenidoSemana;
use App\Models\Docente;
use App\Models\MaterialSemana;
use App\Models\PagoDocente;
use App\Models\Semana;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Panel del docente: ve solo SUS asignaciones (seccion_curso donde docente_id = él)
 * y puede editar el contenido de cada semana.
 */
class DocenteAcademicoController extends Controller
{
    /**
     * GET /docente/mis-clases
     * Lista de cursos que dicta el docente autenticado.
     */
    public function misClases(Request $request): JsonResponse
    {
        $docenteId = $this->docenteId($request);

        $clases = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->join('periodos_academicos as p', 'p.id', '=', 's.periodo_id')
            ->where('sc.docente_id', $docenteId)
            ->whereNull('c.deleted_at')
            ->select([
                'sc.id as seccion_curso_id',
                'c.id as curso_id',
                'c.nombre as curso',
                'c.codigo',
                'c.horas_semana',
                's.nombre as seccion',
                'g.nombre as grado',
                'n.nombre as nivel',
                'p.descripcion as periodo',
                's.id as seccion_id',
            ])
            ->orderBy('n.nombre')
            ->orderBy('g.nombre')
            ->orderBy('c.nombre')
            ->get()
            ->map(function ($r) {
                $estudiantes = DB::table('matriculas')
                    ->where('seccion_id', $r->seccion_id)
                    ->where('estado', 'activa')
                    ->whereNull('deleted_at')
                    ->count();
                return [
                    'seccion_curso_id' => (int) $r->seccion_curso_id,
                    'curso_id'         => (int) $r->curso_id,
                    'curso'            => $r->curso,
                    'codigo'           => $r->codigo,
                    'horas_semana'     => (int) $r->horas_semana,
                    'grado'            => $r->grado,
                    'nivel'            => $r->nivel,
                    'seccion'          => $r->seccion,
                    'periodo'          => $r->periodo,
                    'label'            => "{$r->grado} {$r->seccion} — {$r->nivel}",
                    'estudiantes'      => $estudiantes,
                ];
            });

        return response()->json(['data' => $clases]);
    }

    /**
     * GET /docente/mi-horario
     * Slots de horario de TODAS las clases que dicta el docente.
     */
    public function miHorario(Request $request): JsonResponse
    {
        $docenteId = $this->docenteId($request);

        $slots = DB::table('horarios as h')
            ->join('seccion_curso as sc', 'sc.id', '=', 'h.seccion_curso_id')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->where('sc.docente_id', $docenteId)
            ->whereNull('c.deleted_at')
            ->orderBy('h.dia_semana')
            ->orderBy('h.hora_inicio')
            ->get([
                'sc.id as seccion_curso_id',
                'c.nombre as curso', 'c.codigo',
                'g.nombre as grado', 's.nombre as seccion', 'n.nombre as nivel',
                'h.dia_semana', 'h.hora_inicio', 'h.hora_fin', 'h.aula',
            ])
            ->map(fn ($r) => [
                'seccion_curso_id' => (int) $r->seccion_curso_id,
                'curso'            => $r->curso,
                'codigo'           => $r->codigo,
                'grado'            => $r->grado,
                'seccion'          => $r->seccion,
                'nivel'            => $r->nivel,
                'dia_semana'       => (int) $r->dia_semana,
                'hora_inicio'      => substr((string) $r->hora_inicio, 0, 5),
                'hora_fin'         => substr((string) $r->hora_fin, 0, 5),
                'aula'             => $r->aula,
            ]);

        return response()->json(['data' => $slots]);
    }

    /**
     * GET /docente/mis-clases/{seccionCursoId}
     * Detalle de una clase del docente con bimestres + semanas + contenido.
     */
    public function detalleClase(Request $request, int $seccionCursoId): JsonResponse
    {
        $docenteId = $this->docenteId($request);

        $clase = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->join('periodos_academicos as p', 'p.id', '=', 's.periodo_id')
            ->where('sc.id', $seccionCursoId)
            ->where('sc.docente_id', $docenteId)
            ->whereNull('c.deleted_at')
            ->select([
                'sc.id as seccion_curso_id',
                'c.id as curso_id', 'c.nombre as curso', 'c.codigo', 'c.horas_semana', 'c.descripcion',
                's.id as seccion_id', 's.nombre as seccion',
                'g.nombre as grado', 'n.nombre as nivel',
                'p.descripcion as periodo',
            ])
            ->first();

        abort_if($clase === null, 404, 'Esta clase no te pertenece o no existe.');

        // Bimestres + semanas + contenidos
        $bimestres = Bimestre::with(['semanas' => fn ($q) => $q->orderBy('numero')])
            ->whereHas('periodo', fn ($q) => $q->where('estado', 'activo'))
            ->orderBy('orden')
            ->get();

        $contenidosMap = ContenidoSemana::where('seccion_curso_id', $seccionCursoId)
            ->get()
            ->keyBy('semana_id');

        $materialesMap = MaterialSemana::where('seccion_curso_id', $seccionCursoId)
            ->orderBy('created_at')
            ->get()
            ->groupBy('semana_id');

        $estudiantes = DB::table('matriculas')
            ->where('seccion_id', $clase->seccion_id)
            ->where('estado', 'activa')
            ->whereNull('deleted_at')
            ->count();

        return response()->json([
            'data' => [
                'clase' => [
                    'seccion_curso_id' => (int) $clase->seccion_curso_id,
                    'curso_id'         => (int) $clase->curso_id,
                    'curso'            => $clase->curso,
                    'codigo'           => $clase->codigo,
                    'horas_semana'     => (int) $clase->horas_semana,
                    'descripcion'      => $clase->descripcion,
                    'grado'            => $clase->grado,
                    'nivel'            => $clase->nivel,
                    'seccion'          => $clase->seccion,
                    'periodo'          => $clase->periodo,
                    'label'            => "{$clase->grado} {$clase->seccion} — {$clase->nivel}",
                    'estudiantes'      => $estudiantes,
                ],
                'bimestres' => $bimestres->map(function ($b) use ($contenidosMap, $materialesMap) {
                    return [
                        'id'           => (int) $b->id,
                        'nombre'       => $b->nombre,
                        'orden'        => (int) $b->orden,
                        'fecha_inicio' => $b->fecha_inicio?->toDateString(),
                        'fecha_fin'    => $b->fecha_fin?->toDateString(),
                        'es_actual'    => $b->esActual(),
                        'semanas'      => $b->semanas->map(function ($s) use ($contenidosMap, $materialesMap) {
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
                                'materiales'   => $mats->map(fn ($m) => $this->presentMaterial($m))->values()->all(),
                            ];
                        })->all(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * PUT /docente/mis-clases/{seccionCursoId}/semanas/{semanaId}
     * Crea o actualiza el contenido de una semana (upsert).
     * Si todos los campos vienen vacíos, elimina la fila.
     */
    public function actualizarContenido(Request $request, int $seccionCursoId, int $semanaId): JsonResponse
    {
        $docenteId = $this->docenteId($request);

        // Validar que el docente sea dueño del seccion_curso
        $esMio = DB::table('seccion_curso')
            ->where('id', $seccionCursoId)
            ->where('docente_id', $docenteId)
            ->exists();
        abort_unless($esMio, 403, 'No puedes editar el contenido de un curso que no dictas.');

        // Validar que la semana exista
        $semana = Semana::find($semanaId);
        abort_if($semana === null, 404, 'Semana no encontrada.');

        $data = $request->validate([
            'titulo'       => ['nullable', 'string', 'max:150'],
            'descripcion'  => ['nullable', 'string'],
            'recursos_url' => ['nullable', 'string'],
            'tarea'        => ['nullable', 'string'],
        ]);

        // Si todo viene vacío, eliminamos el contenido (no dejamos basura)
        $vacio = empty(trim((string) ($data['titulo']       ?? '')))
              && empty(trim((string) ($data['descripcion']  ?? '')))
              && empty(trim((string) ($data['recursos_url'] ?? '')))
              && empty(trim((string) ($data['tarea']        ?? '')));

        if ($vacio) {
            ContenidoSemana::where('semana_id', $semanaId)
                ->where('seccion_curso_id', $seccionCursoId)
                ->delete();
            return response()->json(['message' => 'Contenido eliminado.', 'data' => null]);
        }

        $contenido = ContenidoSemana::updateOrCreate(
            ['semana_id' => $semanaId, 'seccion_curso_id' => $seccionCursoId],
            [
                'titulo'       => $data['titulo']       ?? null,
                'descripcion'  => $data['descripcion']  ?? null,
                'recursos_url' => $data['recursos_url'] ?? null,
                'tarea'        => $data['tarea']        ?? null,
            ],
        );

        return response()->json([
            'message' => 'Contenido guardado.',
            'data'    => [
                'titulo'       => $contenido->titulo,
                'descripcion'  => $contenido->descripcion,
                'recursos_url' => $contenido->recursos_url,
                'tarea'        => $contenido->tarea,
            ],
        ]);
    }

    /* ──────────────── Materiales (archivos) ──────────────── */

    /**
     * POST /docente/mis-clases/{seccionCursoId}/semanas/{semanaId}/materiales
     * multipart: archivo (PDF, imagen, doc, etc.)
     */
    public function subirMaterial(Request $request, int $seccionCursoId, int $semanaId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);
        abort_if(Semana::find($semanaId) === null, 404, 'Semana no encontrada.');

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,zip,txt', 'max:10240'],
        ]);

        $file = $request->file('archivo');
        $path = $file->store("materiales_semana/{$seccionCursoId}/{$semanaId}", 'public');

        $material = MaterialSemana::create([
            'semana_id'        => $semanaId,
            'seccion_curso_id' => $seccionCursoId,
            'nombre_original'  => $file->getClientOriginalName(),
            'ruta'             => $path,
            'tipo'             => $file->getClientMimeType(),
            'tamano'           => (int) $file->getSize(),
            'subido_por'       => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Archivo subido.',
            'data'    => $this->presentMaterial($material),
        ], 201);
    }

    /**
     * DELETE /docente/mis-clases/{seccionCursoId}/semanas/{semanaId}/materiales/{materialId}
     */
    public function eliminarMaterial(Request $request, int $seccionCursoId, int $semanaId, int $materialId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);

        $material = MaterialSemana::where('id', $materialId)
            ->where('semana_id', $semanaId)
            ->where('seccion_curso_id', $seccionCursoId)
            ->first();
        abort_if($material === null, 404, 'Material no encontrado.');

        // Borra el archivo físico también
        if ($material->ruta && Storage::disk('public')->exists($material->ruta)) {
            Storage::disk('public')->delete($material->ruta);
        }
        $material->delete();

        return response()->json(['message' => 'Archivo eliminado.']);
    }

    private function validarPropiedadCurso(int $seccionCursoId, int $docenteId): void
    {
        $esMio = DB::table('seccion_curso')
            ->where('id', $seccionCursoId)
            ->where('docente_id', $docenteId)
            ->exists();
        abort_unless($esMio, 403, 'No puedes editar materiales de un curso que no dictas.');
    }

    private function presentMaterial(MaterialSemana $m): array
    {
        return [
            'id'             => (int) $m->id,
            'nombre'         => $m->nombre_original,
            'url'            => $m->ruta ? asset('storage/' . $m->ruta) : null,
            'tipo'           => $m->tipo,
            'tamano'         => (int) $m->tamano,
            'tamano_legible' => $this->tamanoLegible((int) $m->tamano),
            'subido_en'      => $m->created_at?->toIso8601String(),
        ];
    }

    private function tamanoLegible(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }

    /* ──────────────── Alumnos / Notas / Asistencia ──────────────── */

    /**
     * GET /docente/mis-clases/{seccionCursoId}/alumnos
     */
    public function alumnos(Request $request, int $seccionCursoId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);

        $alumnos = $this->alumnosDeSeccionCurso($seccionCursoId);

        return response()->json(['data' => $alumnos]);
    }

    /** Helper: matrículas activas de la sección a la que pertenece el seccion_curso. */
    private function alumnosDeSeccionCurso(int $seccionCursoId): array
    {
        $seccionId = DB::table('seccion_curso')->where('id', $seccionCursoId)->value('seccion_id');

        return DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->where('m.seccion_id', $seccionId)
            ->where('m.estado', 'activa')
            ->whereNull('m.deleted_at')
            ->orderBy('e.apellidos')->orderBy('e.nombres')
            ->select([
                'm.id as matricula_id',
                'e.codigo_estudiante',
                'e.nombres',
                'e.apellidos',
            ])
            ->get()
            ->map(fn ($r) => [
                'matricula_id'      => (int) $r->matricula_id,
                'codigo_estudiante' => $r->codigo_estudiante,
                'nombres'           => $r->nombres,
                'apellidos'         => $r->apellidos,
                'nombre'            => trim("{$r->apellidos}, {$r->nombres}"),
            ])
            ->all();
    }

    /**
     * GET /docente/mis-clases/{seccionCursoId}/notas?bimestre_id=
     */
    public function notas(Request $request, int $seccionCursoId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);

        $bimestreId = (int) $request->query('bimestre_id');
        abort_if($bimestreId === 0, 422, 'Falta bimestre_id.');

        $cursoId = DB::table('seccion_curso')->where('id', $seccionCursoId)->value('curso_id');

        $competencias = Competencia::where('curso_id', $cursoId)
            ->orderBy('orden')->orderBy('id')
            ->get(['id', 'nombre']);

        $alumnos = $this->alumnosDeSeccionCurso($seccionCursoId);

        $notas = Calificacion::where('seccion_curso_id', $seccionCursoId)
            ->where('bimestre_id', $bimestreId)
            ->get(['matricula_id', 'competencia_id', 'nota', 'conclusion_descriptiva'])
            ->map(fn ($c) => [
                'matricula_id'   => (int) $c->matricula_id,
                'competencia_id' => (int) $c->competencia_id,
                'nota'           => $c->nota,
                'conclusion'     => $c->conclusion_descriptiva,
            ]);

        return response()->json([
            'data' => [
                'competencias' => $competencias,
                'alumnos'      => $alumnos,
                'notas'        => $notas,
            ],
        ]);
    }

    /**
     * PUT /docente/mis-clases/{seccionCursoId}/notas
     * body: { bimestre_id, items: [{matricula_id, competencia_id, nota|null, conclusion?}] }
     */
    public function guardarNotas(Request $request, int $seccionCursoId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);

        $data = $request->validate([
            'bimestre_id'             => ['required', 'integer', 'exists:bimestres,id'],
            'items'                   => ['required', 'array'],
            'items.*.matricula_id'    => ['required', 'integer'],
            'items.*.competencia_id'  => ['required', 'integer'],
            'items.*.nota'            => ['nullable', 'in:AD,A,B,C'],
            'items.*.conclusion'      => ['nullable', 'string'],
        ]);

        $cursoId = DB::table('seccion_curso')->where('id', $seccionCursoId)->value('curso_id');
        $compValidas = Competencia::where('curso_id', $cursoId)->pluck('id')->all();

        DB::transaction(function () use ($data, $seccionCursoId, $compValidas) {
            foreach ($data['items'] as $item) {
                if (! in_array($item['competencia_id'], $compValidas, true)) {
                    continue; // ignora competencias que no son del curso
                }
                Calificacion::updateOrCreate(
                    [
                        'matricula_id'   => $item['matricula_id'],
                        'competencia_id' => $item['competencia_id'],
                        'bimestre_id'    => $data['bimestre_id'],
                    ],
                    [
                        'seccion_curso_id'       => $seccionCursoId,
                        'nota'                   => $item['nota'] ?? null,
                        'conclusion_descriptiva' => $item['conclusion'] ?? null,
                    ],
                );
            }
        });

        return response()->json(['message' => 'Notas guardadas.']);
    }

    /**
     * GET /docente/mis-clases/{seccionCursoId}/asistencia?fecha=YYYY-MM-DD
     */
    public function asistencia(Request $request, int $seccionCursoId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);

        $fecha = $request->query('fecha');
        abort_if(empty($fecha), 422, 'Falta fecha.');

        $alumnos = $this->alumnosDeSeccionCurso($seccionCursoId);

        $estados = Asistencia::where('seccion_curso_id', $seccionCursoId)
            ->whereDate('fecha', $fecha)
            ->pluck('estado', 'matricula_id');

        $alumnos = array_map(function ($a) use ($estados) {
            $a['estado'] = $estados[$a['matricula_id']] ?? null;
            return $a;
        }, $alumnos);

        return response()->json(['data' => ['fecha' => $fecha, 'alumnos' => $alumnos]]);
    }

    /**
     * PUT /docente/mis-clases/{seccionCursoId}/asistencia
     * body: { fecha, items: [{matricula_id, estado}] }
     */
    public function guardarAsistencia(Request $request, int $seccionCursoId): JsonResponse
    {
        $docenteId = $this->docenteId($request);
        $this->validarPropiedadCurso($seccionCursoId, $docenteId);

        $data = $request->validate([
            'fecha'                => ['required', 'date'],
            'items'                => ['required', 'array'],
            'items.*.matricula_id' => ['required', 'integer'],
            'items.*.estado'       => ['required', 'in:presente,tarde,falta,justificada'],
        ]);

        DB::transaction(function () use ($data, $seccionCursoId) {
            foreach ($data['items'] as $item) {
                Asistencia::updateOrCreate(
                    [
                        'matricula_id'     => $item['matricula_id'],
                        'seccion_curso_id' => $seccionCursoId,
                        'fecha'            => $data['fecha'],
                    ],
                    ['estado' => $item['estado']],
                );
            }
        });

        return response()->json(['message' => 'Asistencia guardada.']);
    }

    /* ──────────────── Pagos del docente ──────────────── */

    /**
     * GET /docente/mis-pagos
     * Pagos del docente autenticado + resumen (total pagado / pendiente).
     */
    public function misPagos(Request $request): JsonResponse
    {
        $docenteId = $this->docenteId($request);

        $pagos = PagoDocente::where('docente_id', $docenteId)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        $totalPagado = (float) $pagos->where('estado', 'pagado')->sum('monto');
        $totalPendiente = (float) $pagos->where('estado', 'pendiente')->sum('monto');

        return response()->json([
            'data' => [
                'pagos' => $pagos->map(fn ($p) => [
                    'id'          => (int) $p->id,
                    'concepto'    => $p->concepto,
                    'descripcion' => $p->descripcion,
                    'monto'       => (float) $p->monto,
                    'mes'         => $p->mes !== null ? (int) $p->mes : null,
                    'anio'        => (int) $p->anio,
                    'estado'      => $p->estado,
                    'fecha_pago'  => $p->fecha_pago?->toDateString(),
                    'metodo'      => $p->metodo,
                ])->values(),
                'total_pagado'    => $totalPagado,
                'total_pendiente' => $totalPendiente,
            ],
        ]);
    }

    /* ──────────────── helpers ──────────────── */

    /**
     * Obtiene el docente_id correspondiente al usuario autenticado.
     * El usuario debe tener rol='docente' Y existir una fila en docentes.
     */
    private function docenteId(Request $request): int
    {
        $user = $request->user();
        abort_if($user === null, 401);
        abort_unless(($user->rol ?? null) === 'docente', 403, 'Esta sección es solo para docentes.');

        $docente = Docente::where('usuario_id', $user->id)->first();
        abort_if($docente === null, 404, 'No tienes perfil de docente registrado.');

        return (int) $docente->id;
    }
}
