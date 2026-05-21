<?php

namespace App\Modules\Academico\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use App\Models\Seccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin gestiona los horarios de una sección.
 * El usuario ve la grilla actual y la edita de forma batch.
 */
class HorarioController extends Controller
{
    /**
     * GET /secciones/{seccion}/horarios
     * Devuelve: cursos asignados de esa sección + horarios actuales de cada uno.
     */
    public function show(Seccion $seccion): JsonResponse
    {
        $seccion->load(['grado.nivel', 'periodo']);

        $cursosAsignados = DB::table('seccion_curso as sc')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->leftJoin('docentes as d', 'd.id', '=', 'sc.docente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('sc.seccion_id', $seccion->id)
            ->whereNull('c.deleted_at')
            ->select([
                'sc.id as seccion_curso_id',
                'c.id as curso_id',
                'c.nombre as curso',
                'c.codigo',
                'c.horas_semana',
                'u.nombres as docente_nombres',
                'u.apellidos as docente_apellidos',
            ])
            ->orderBy('c.nombre')
            ->get();

        $horarios = DB::table('horarios as h')
            ->join('seccion_curso as sc', 'sc.id', '=', 'h.seccion_curso_id')
            ->where('sc.seccion_id', $seccion->id)
            ->select(['h.id', 'h.seccion_curso_id', 'h.dia_semana', 'h.hora_inicio', 'h.hora_fin', 'h.aula'])
            ->get()
            ->map(fn ($h) => [
                'id'               => (int) $h->id,
                'seccion_curso_id' => (int) $h->seccion_curso_id,
                'dia_semana'       => (int) $h->dia_semana,
                'hora_inicio'      => substr($h->hora_inicio, 0, 5),
                'hora_fin'         => substr($h->hora_fin, 0, 5),
                'aula'             => $h->aula,
            ]);

        return response()->json([
            'data' => [
                'seccion' => [
                    'id'       => (int) $seccion->id,
                    'nombre'   => $seccion->nombre,
                    'grado'    => $seccion->grado?->nombre,
                    'nivel'    => $seccion->grado?->nivel?->nombre,
                    'periodo'  => $seccion->periodo?->descripcion,
                    'label'    => "{$seccion->grado?->nombre} {$seccion->nombre} — {$seccion->grado?->nivel?->nombre}",
                ],
                'cursos'   => $cursosAsignados,
                'horarios' => $horarios,
            ],
        ]);
    }

    /**
     * PUT /secciones/{seccion}/horarios
     * Reemplaza todos los horarios de esa sección con los provistos.
     * Payload: { horarios: [{ seccion_curso_id, dia_semana, hora_inicio, hora_fin, aula? }] }
     */
    public function update(Request $request, Seccion $seccion): JsonResponse
    {
        $data = $request->validate([
            'horarios'                       => ['present', 'array'],
            'horarios.*.seccion_curso_id'    => ['required', 'integer'],
            'horarios.*.dia_semana'          => ['required', 'integer', 'min:1', 'max:7'],
            'horarios.*.hora_inicio'         => ['required', 'date_format:H:i'],
            'horarios.*.hora_fin'            => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
            'horarios.*.aula'                => ['nullable', 'string', 'max:50'],
        ]);

        // Validar que todos los seccion_curso_id pertenezcan a esta sección
        $idsValidos = DB::table('seccion_curso')
            ->where('seccion_id', $seccion->id)
            ->pluck('id')
            ->all();

        foreach ($data['horarios'] as $h) {
            abort_unless(
                in_array((int) $h['seccion_curso_id'], $idsValidos, true),
                422,
                "El curso con seccion_curso_id={$h['seccion_curso_id']} no pertenece a esta sección.",
            );
        }

        DB::transaction(function () use ($data, $seccion, $idsValidos) {
            // Limpia todos los horarios anteriores de esta sección
            DB::table('horarios')->whereIn('seccion_curso_id', $idsValidos)->delete();

            // Inserta los nuevos
            $rows = [];
            $now = now();
            foreach ($data['horarios'] as $h) {
                $rows[] = [
                    'seccion_curso_id' => (int) $h['seccion_curso_id'],
                    'dia_semana'       => (int) $h['dia_semana'],
                    'hora_inicio'      => $h['hora_inicio'] . ':00',
                    'hora_fin'         => $h['hora_fin'] . ':00',
                    'aula'             => $h['aula'] ?? null,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
            if (! empty($rows)) {
                DB::table('horarios')->insert($rows);
            }
        });

        return response()->json([
            'message' => 'Horario de ' . $seccion->nombre . ' actualizado (' . count($data['horarios']) . ' slots).',
        ]);
    }
}
