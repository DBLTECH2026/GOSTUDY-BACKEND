<?php

namespace App\Modules\Matricula\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Modules\Matricula\Events\MatriculaAprobada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Stub creado por Persona C porque B no lo tenía. Solo index + store
 * (creación manual de matrícula para un estudiante ya existente).
 * Al crear, dispara MatriculaAprobada → mis pagos se generan automático.
 */
class MatriculaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('matriculas as m')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->join('periodos_academicos as p', 'p.id', '=', 'm.periodo_id')
            ->whereNull('m.deleted_at')
            ->select([
                'm.id',
                'm.fecha_matricula',
                'm.estado',
                'e.id as estudiante_id',
                'e.codigo_estudiante',
                'e.nombres as est_nombres',
                'e.apellidos as est_apellidos',
                'e.dni',
                'n.nombre as nivel',
                'g.nombre as grado',
                's.nombre as seccion',
                'p.descripcion as periodo',
            ])
            ->orderByDesc('m.fecha_matricula');

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('e.nombres', 'like', "%{$q}%")
                  ->orWhere('e.apellidos', 'like', "%{$q}%")
                  ->orWhere('e.dni', 'like', "%{$q}%")
                  ->orWhere('e.codigo_estudiante', 'like', "%{$q}%");
            });
        }

        $items = $query->get()->map(fn ($r) => [
            'id'                => (int) $r->id,
            'fecha_matricula'   => $r->fecha_matricula,
            'estado'            => $r->estado,
            'estudiante'        => [
                'id'                => (int) $r->estudiante_id,
                'codigo_estudiante' => $r->codigo_estudiante,
                'nombre_completo'   => trim($r->est_nombres . ' ' . $r->est_apellidos),
                'dni'               => $r->dni,
            ],
            'nivel'   => $r->nivel,
            'grado'   => $r->grado,
            'seccion' => $r->seccion,
            'periodo' => $r->periodo,
        ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'estudiante_id' => ['required', 'integer', 'exists:estudiantes,id'],
            'seccion_id'    => ['required', 'integer', 'exists:secciones,id'],
            'periodo_id'    => ['nullable', 'integer', 'exists:periodos_academicos,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $estudiante = Estudiante::findOrFail($data['estudiante_id']);
        $seccion    = Seccion::with('periodo')->findOrFail($data['seccion_id']);

        $periodoId = $data['periodo_id']
            ?? $seccion->periodo_id
            ?? PeriodoAcademico::where('estado', 'activo')->value('id');

        if (! $periodoId) {
            return response()->json(['message' => 'No hay periodo activo.'], 422);
        }

        // Ya existe matrícula para ese estudiante + periodo
        $existe = Matricula::where('estudiante_id', $estudiante->id)
            ->where('periodo_id', $periodoId)
            ->whereNull('deleted_at')
            ->exists();
        if ($existe) {
            return response()->json([
                'message' => 'Este estudiante ya tiene matrícula activa en el periodo seleccionado.',
            ], 422);
        }

        if ($seccion->cupoDisponible() <= 0) {
            return response()->json(['message' => 'La sección no tiene cupos disponibles.'], 422);
        }

        $matricula = Matricula::create([
            'estudiante_id'   => $estudiante->id,
            'periodo_id'      => $periodoId,
            'seccion_id'      => $seccion->id,
            'fecha_matricula' => now()->toDateString(),
            'estado'          => Matricula::ESTADO_ACTIVA,
            'observaciones'   => $data['observaciones'] ?? null,
            'registrado_por'  => $request->user()?->id,
        ]);

        MatriculaAprobada::dispatch($matricula);

        return response()->json([
            'message' => 'Matrícula creada. Los pagos del periodo se generaron automáticamente.',
            'data'    => [
                'id'                => $matricula->id,
                'estudiante'        => trim($estudiante->nombres . ' ' . $estudiante->apellidos),
                'seccion'           => $seccion->nombre,
                'fecha_matricula'   => $matricula->fecha_matricula->toDateString(),
            ],
        ], 201);
    }

    public function catalogo(Request $request): JsonResponse
    {
        // Datos auxiliares para el modal: lista de estudiantes sin matrícula activa
        // en el periodo activo, secciones con cupo, periodo activo.
        $periodo = PeriodoAcademico::where('estado', 'activo')->first();
        if (! $periodo) {
            return response()->json(['data' => ['periodo' => null, 'estudiantes' => [], 'secciones' => []]]);
        }

        $idsConMatricula = Matricula::where('periodo_id', $periodo->id)
            ->whereNull('deleted_at')
            ->pluck('estudiante_id')
            ->all();

        $estudiantes = Estudiante::whereNotIn('id', $idsConMatricula)
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->get()
            ->map(fn ($e) => [
                'id'                => $e->id,
                'codigo_estudiante' => $e->codigo_estudiante,
                'dni'               => $e->dni,
                'nombre_completo'   => trim($e->nombres . ' ' . $e->apellidos),
            ]);

        $secciones = DB::table('secciones as s')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->where('s.periodo_id', $periodo->id)
            ->select([
                's.id',
                's.nombre',
                's.capacidad',
                'g.nombre as grado',
                'n.nombre as nivel',
            ])
            ->orderBy('n.orden')
            ->orderBy('g.orden')
            ->get()
            ->map(function ($r) {
                $matriculados = DB::table('matriculas')
                    ->where('seccion_id', $r->id)
                    ->where('estado', 'activa')
                    ->whereNull('deleted_at')
                    ->count();
                return [
                    'id'           => (int) $r->id,
                    'label'        => "{$r->nivel} · {$r->grado} — {$r->nombre}",
                    'capacidad'    => (int) $r->capacidad,
                    'matriculados' => $matriculados,
                    'cupo'         => max(0, (int) $r->capacidad - $matriculados),
                ];
            });

        return response()->json([
            'data' => [
                'periodo' => [
                    'id'          => $periodo->id,
                    'descripcion' => $periodo->descripcion,
                ],
                'estudiantes' => $estudiantes,
                'secciones'   => $secciones,
            ],
        ]);
    }
}
