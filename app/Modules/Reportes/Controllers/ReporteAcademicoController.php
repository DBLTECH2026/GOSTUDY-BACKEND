<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GOSTUDY — Persona C · Reportes académicos (acta de notas, desaprobados,
 * boleta por alumno, asistencia). Devuelven siempre ['data' => ...].
 */
class ReporteAcademicoController extends Controller
{
    /**
     * GET /api/v1/reportes/clases
     * Lista de seccion_curso disponibles para poblar selectores grado/sección/curso.
     */
    public function clases(Request $request): JsonResponse
    {
        $rows = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->whereNull('c.deleted_at')
            ->orderBy('n.id')->orderBy('g.id')->orderBy('s.nombre')->orderBy('c.nombre')
            ->get([
                'sc.id as seccion_curso_id',
                'g.id as grado_id', 'g.nombre as grado',
                's.id as seccion_id', 's.nombre as seccion',
                'n.nombre as nivel',
                'c.id as curso_id', 'c.nombre as curso',
            ])
            ->map(fn ($r) => [
                'seccion_curso_id' => (int) $r->seccion_curso_id,
                'grado_id'         => (int) $r->grado_id,
                'grado'            => $r->grado,
                'seccion_id'       => (int) $r->seccion_id,
                'seccion'          => $r->seccion,
                'nivel'            => $r->nivel,
                'curso_id'         => (int) $r->curso_id,
                'curso'            => $r->curso,
                'grado_label'      => "{$r->grado} \"{$r->seccion}\" — {$r->nivel}",
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/v1/reportes/alumnos?seccion_id=
     * Lista de alumnos (matrículas activas) para poblar el selector de alumno.
     * Si no se pasa seccion_id, devuelve todos.
     */
    public function alumnos(Request $request): JsonResponse
    {
        $seccionId = (int) $request->query('seccion_id');

        $q = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->where('m.estado', 'activa')
            ->whereNull('m.deleted_at');

        if ($seccionId > 0) {
            $q->where('m.seccion_id', $seccionId);
        }

        $rows = $q->orderBy('g.id')->orderBy('s.nombre')->orderBy('e.apellidos')
            ->get([
                'm.id as matricula_id',
                'm.seccion_id',
                'e.nombres', 'e.apellidos', 'e.codigo_estudiante',
                'g.nombre as grado', 's.nombre as seccion',
            ])
            ->map(fn ($r) => [
                'matricula_id' => (int) $r->matricula_id,
                'seccion_id'   => (int) $r->seccion_id,
                'nombre'       => trim("{$r->apellidos}, {$r->nombres}"),
                'codigo'       => $r->codigo_estudiante,
                'grado'        => $r->grado,
                'seccion'      => $r->seccion,
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/v1/reportes/acta-notas?seccion_curso_id=&bimestre_id=
     *
     * Tabla alumnos × competencias del curso para un bimestre.
     */
    public function actaNotas(Request $request): JsonResponse
    {
        $seccionCursoId = (int) $request->query('seccion_curso_id');
        $bimestreId     = (int) $request->query('bimestre_id');
        abort_if($seccionCursoId === 0, 422, 'Falta seccion_curso_id.');
        abort_if($bimestreId === 0, 422, 'Falta bimestre_id.');

        $clase = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->join('periodos_academicos as p', 'p.id', '=', 's.periodo_id')
            ->leftJoin('docentes as d', 'd.id', '=', 'sc.docente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('sc.id', $seccionCursoId)
            ->select([
                'sc.id as seccion_curso_id',
                'sc.curso_id',
                'sc.seccion_id',
                'c.nombre as curso',
                'g.nombre as grado',
                's.nombre as seccion',
                'n.nombre as nivel',
                'p.descripcion as periodo',
                DB::raw("TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) as docente"),
            ])
            ->first();

        abort_if($clase === null, 404, 'La clase (seccion_curso) no existe.');

        $competencias = DB::table('competencias')
            ->where('curso_id', $clase->curso_id)
            ->whereNull('deleted_at')
            ->orderBy('orden')->orderBy('id')
            ->get(['id', 'nombre'])
            ->map(fn ($r) => [
                'id'     => (int) $r->id,
                'nombre' => $r->nombre,
            ])
            ->values();

        $alumnos = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->where('m.seccion_id', $clase->seccion_id)
            ->where('m.estado', 'activa')
            ->whereNull('m.deleted_at')
            ->orderBy('e.apellidos')->orderBy('e.nombres')
            ->select(['m.id as matricula_id', 'e.nombres', 'e.apellidos'])
            ->get()
            ->map(fn ($r) => [
                'matricula_id' => (int) $r->matricula_id,
                'nombre'       => trim("{$r->apellidos}, {$r->nombres}"),
            ])
            ->values();

        $notas = DB::table('calificaciones')
            ->where('seccion_curso_id', $seccionCursoId)
            ->where('bimestre_id', $bimestreId)
            ->get(['matricula_id', 'competencia_id', 'nota'])
            ->map(fn ($r) => [
                'matricula_id'   => (int) $r->matricula_id,
                'competencia_id' => (int) $r->competencia_id,
                'nota'           => $r->nota,
            ])
            ->values();

        return response()->json([
            'data' => [
                'clase' => [
                    'curso'   => $clase->curso,
                    'grado'   => $clase->grado,
                    'seccion' => $clase->seccion,
                    'nivel'   => $clase->nivel,
                    'periodo' => $clase->periodo,
                    'docente' => $clase->docente !== '' ? $clase->docente : null,
                ],
                'competencias' => $competencias,
                'alumnos'      => $alumnos,
                'notas'        => $notas,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/desaprobados?seccion_curso_id?&bimestre_id?&incluir_b=0|1
     *
     * Lista plana de notas desaprobatorias ('C', o 'C'/'B' si incluir_b=1).
     * Sin seccion_curso_id → global (todas las clases).
     */
    public function desaprobados(Request $request): JsonResponse
    {
        $incluirB = (int) $request->query('incluir_b', '0') === 1;
        $notasDesaprob = $incluirB ? ['C', 'B'] : ['C'];

        $query = DB::table('calificaciones as cal')
            ->join('matriculas as m', 'm.id', '=', 'cal.matricula_id')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('seccion_curso as sc', 'sc.id', '=', 'cal.seccion_curso_id')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('competencias as comp', 'comp.id', '=', 'cal.competencia_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->whereIn('cal.nota', $notasDesaprob);

        if ($request->filled('seccion_curso_id')) {
            $query->where('cal.seccion_curso_id', $request->integer('seccion_curso_id'));
        }

        if ($request->filled('bimestre_id')) {
            $query->where('cal.bimestre_id', $request->integer('bimestre_id'));
        }

        $rows = $query
            ->orderBy('e.apellidos')->orderBy('e.nombres')
            ->orderBy('c.nombre')
            ->select([
                'e.nombres',
                'e.apellidos',
                'c.nombre as curso',
                'g.nombre as grado',
                's.nombre as seccion',
                'comp.nombre as competencia',
                'cal.nota',
            ])
            ->get()
            ->map(fn ($r) => [
                'alumno'      => trim("{$r->apellidos}, {$r->nombres}"),
                'curso'       => $r->curso,
                'grado'       => $r->grado,
                'seccion'     => $r->seccion,
                'competencia' => $r->competencia,
                'nota'        => $r->nota,
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/v1/reportes/boleta?matricula_id=
     *
     * Libreta de un alumno: todas sus calificaciones agrupadas por
     * curso → competencia → bimestre.
     */
    public function boleta(Request $request): JsonResponse
    {
        $matriculaId = (int) $request->query('matricula_id');
        abort_if($matriculaId === 0, 422, 'Falta matricula_id.');

        $alumno = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('periodos_academicos as p', 'p.id', '=', 's.periodo_id')
            ->where('m.id', $matriculaId)
            ->select([
                'e.nombres',
                'e.apellidos',
                'e.codigo_estudiante',
                'g.nombre as grado',
                's.nombre as seccion',
                'p.descripcion as periodo',
            ])
            ->first();

        abort_if($alumno === null, 404, 'La matrícula no existe.');

        $rows = DB::table('calificaciones as cal')
            ->join('seccion_curso as sc', 'sc.id', '=', 'cal.seccion_curso_id')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('competencias as comp', 'comp.id', '=', 'cal.competencia_id')
            ->join('bimestres as b', 'b.id', '=', 'cal.bimestre_id')
            ->where('cal.matricula_id', $matriculaId)
            ->orderBy('c.nombre')
            ->orderBy('comp.orden')->orderBy('comp.id')
            ->orderBy('b.orden')
            ->select([
                'c.nombre as curso',
                'comp.nombre as competencia',
                'b.nombre as bimestre',
                'b.orden as bimestre_orden',
                'cal.nota',
            ])
            ->get();

        // Agrupar curso → competencia → bimestres
        $cursos = [];
        foreach ($rows as $r) {
            $cursos[$r->curso] ??= [];
            $cursos[$r->curso][$r->competencia] ??= [];
            $cursos[$r->curso][$r->competencia][] = [
                'bimestre' => $r->bimestre,
                'nota'     => $r->nota,
            ];
        }

        $cursosOut = [];
        foreach ($cursos as $curso => $competencias) {
            $compsOut = [];
            foreach ($competencias as $nombreComp => $bimestres) {
                $compsOut[] = [
                    'nombre'    => $nombreComp,
                    'bimestres' => $bimestres,
                ];
            }
            $cursosOut[] = [
                'curso'        => $curso,
                'competencias' => $compsOut,
            ];
        }

        return response()->json([
            'data' => [
                'alumno' => [
                    'nombre'  => trim("{$alumno->apellidos}, {$alumno->nombres}"),
                    'codigo'  => $alumno->codigo_estudiante,
                    'grado'   => $alumno->grado,
                    'seccion' => $alumno->seccion,
                ],
                'periodo' => $alumno->periodo,
                'cursos'  => $cursosOut,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/ficha-matricula?matricula_id=
     *
     * Ficha de matrícula completa de un alumno: datos del estudiante,
     * datos de la matrícula y datos del apoderado (desde la inscripción).
     */
    public function fichaMatricula(Request $request): JsonResponse
    {
        $matriculaId = (int) $request->query('matricula_id');
        abort_if($matriculaId === 0, 422, 'Falta matricula_id.');

        $row = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->join('periodos_academicos as p', 'p.id', '=', 'm.periodo_id')
            ->leftJoin('inscripciones as i', 'i.id', '=', 'm.inscripcion_id')
            ->where('m.id', $matriculaId)
            ->whereNull('m.deleted_at')
            ->select([
                'e.id as estudiante_id',
                'e.codigo_estudiante',
                'e.dni',
                'e.nombres',
                'e.apellidos',
                'e.fecha_nacimiento',
                'e.sexo',
                'e.direccion',
                'e.departamento',
                'e.provincia',
                'e.distrito',
                'e.ie_procedencia',
                'e.anio_procedencia',
                'e.foto_url',
                'm.fecha_matricula',
                'm.estado as estado_matricula',
                'n.nombre as nivel',
                'g.nombre as grado',
                's.nombre as seccion',
                'p.descripcion as periodo',
                'i.datos_familiares',
            ])
            ->first();

        abort_if($row === null, 404, 'La matrícula no existe.');

        // Apoderado: fuente canónica = perfiles_familiares (titular o primero).
        // Fallback = JSON datos_familiares de la inscripción (alumnos sin perfil).
        $perfil = DB::table('perfiles_familiares')
            ->where('estudiante_id', $row->estudiante_id)
            ->orderByDesc('es_titular')
            ->orderBy('id')
            ->first();

        if ($perfil !== null) {
            $apoderado = [
                'nombre'     => trim("{$perfil->apellidos}, {$perfil->nombres}"),
                'dni'        => $perfil->dni,
                'parentesco' => $perfil->parentesco ?: $perfil->tipo,
                'telefono'   => $perfil->telefono,
                'email'      => $perfil->email,
                'ocupacion'  => $perfil->ocupacion,
            ];
        } else {
            $familiares = $row->datos_familiares
                ? json_decode((string) $row->datos_familiares, true)
                : null;
            $principal = $familiares['apoderado_principal'] ?? null;

            $apoderado = $principal ? [
                'nombre'     => trim(($principal['apellidos'] ?? '') . ', ' . ($principal['nombres'] ?? ''), ', '),
                'dni'        => $principal['dni']      ?? null,
                'parentesco' => $principal['tipo']     ?? null,
                'telefono'   => $principal['telefono'] ?? null,
                'email'      => $principal['email']    ?? null,
                'ocupacion'  => $principal['ocupacion'] ?? null,
            ] : null;
        }

        return response()->json([
            'data' => [
                'estudiante' => [
                    'codigo'           => $row->codigo_estudiante,
                    'dni'              => $row->dni,
                    'nombre'           => trim("{$row->apellidos}, {$row->nombres}"),
                    'fecha_nacimiento' => $row->fecha_nacimiento,
                    'sexo'             => $row->sexo,
                    'direccion'        => $row->direccion,
                    'departamento'     => $row->departamento,
                    'provincia'        => $row->provincia,
                    'distrito'         => $row->distrito,
                    'ie_procedencia'   => $row->ie_procedencia,
                    'anio_procedencia' => $row->anio_procedencia,
                    'foto_url'         => $row->foto_url,
                ],
                'matricula' => [
                    'periodo'        => $row->periodo,
                    'nivel'          => $row->nivel,
                    'grado'          => $row->grado,
                    'seccion'        => $row->seccion,
                    'fecha'          => $row->fecha_matricula,
                    'estado'         => $row->estado_matricula,
                ],
                'apoderado' => $apoderado,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/asistencia?seccion_curso_id=&desde=&hasta=
     *
     * Matriz alumno × fecha + totales por estado.
     */
    public function asistenciaReporte(Request $request): JsonResponse
    {
        $seccionCursoId = (int) $request->query('seccion_curso_id');
        abort_if($seccionCursoId === 0, 422, 'Falta seccion_curso_id.');

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $clase = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->join('secciones as s', 's.id', '=', 'sc.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->join('periodos_academicos as p', 'p.id', '=', 's.periodo_id')
            ->where('sc.id', $seccionCursoId)
            ->select([
                'sc.seccion_id',
                'c.nombre as curso',
                'g.nombre as grado',
                's.nombre as seccion',
                'n.nombre as nivel',
                'p.descripcion as periodo',
            ])
            ->first();

        abort_if($clase === null, 404, 'La clase (seccion_curso) no existe.');

        $alumnos = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->where('m.seccion_id', $clase->seccion_id)
            ->where('m.estado', 'activa')
            ->whereNull('m.deleted_at')
            ->orderBy('e.apellidos')->orderBy('e.nombres')
            ->select(['m.id as matricula_id', 'e.nombres', 'e.apellidos'])
            ->get()
            ->map(fn ($r) => [
                'matricula_id' => (int) $r->matricula_id,
                'nombre'       => trim("{$r->apellidos}, {$r->nombres}"),
            ])
            ->values();

        $registrosQuery = DB::table('asistencias')
            ->where('seccion_curso_id', $seccionCursoId);

        if (! empty($desde)) {
            $registrosQuery->whereDate('fecha', '>=', $desde);
        }
        if (! empty($hasta)) {
            $registrosQuery->whereDate('fecha', '<=', $hasta);
        }

        $registrosRaw = $registrosQuery
            ->orderBy('fecha')
            ->get(['matricula_id', 'fecha', 'estado']);

        $registros = $registrosRaw->map(fn ($r) => [
            'matricula_id' => (int) $r->matricula_id,
            'fecha'        => substr((string) $r->fecha, 0, 10),
            'estado'       => $r->estado,
        ])->values();

        $fechas = $registros->pluck('fecha')->unique()->sort()->values();

        // Totales por alumno y estado
        $totales = $alumnos->map(function ($a) use ($registros) {
            $delAlumno = $registros->where('matricula_id', $a['matricula_id']);
            return [
                'matricula_id' => $a['matricula_id'],
                'presente'     => $delAlumno->where('estado', 'presente')->count(),
                'tarde'        => $delAlumno->where('estado', 'tarde')->count(),
                'falta'        => $delAlumno->where('estado', 'falta')->count(),
                'justificada'  => $delAlumno->where('estado', 'justificada')->count(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'clase' => [
                    'curso'   => $clase->curso,
                    'grado'   => $clase->grado,
                    'seccion' => $clase->seccion,
                    'nivel'   => $clase->nivel,
                    'periodo' => $clase->periodo,
                ],
                'alumnos'   => $alumnos,
                'fechas'    => $fechas,
                'registros' => $registros,
                'totales'   => $totales,
            ],
        ]);
    }
}
