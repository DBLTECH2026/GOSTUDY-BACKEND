<?php

namespace App\Modules\Academico\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Competencia;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompetenciaController extends Controller
{
    /**
     * GET /cursos/{curso}/competencias
     * Lista las competencias de un curso ordenadas por `orden`.
     */
    public function index(Curso $curso): JsonResponse
    {
        $competencias = Competencia::where('curso_id', $curso->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(fn (Competencia $c) => $this->present($c));

        return response()->json(['data' => $competencias]);
    }

    /**
     * POST /cursos/{curso}/competencias
     * Crea una competencia para el curso. Si no se envía `orden`,
     * se ubica al final de la lista existente.
     */
    public function store(Request $request, Curso $curso): JsonResponse
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden'       => ['nullable', 'integer', 'min:0'],
        ]);

        $orden = $data['orden'] ?? ((int) Competencia::where('curso_id', $curso->id)->max('orden') + 1);

        $competencia = Competencia::create([
            'curso_id'    => $curso->id,
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'orden'       => $orden,
        ]);

        return response()->json([
            'message' => 'Competencia creada.',
            'data'    => $this->present($competencia),
        ], 201);
    }

    /**
     * PUT /competencias/{competencia}
     * Actualiza nombre, descripción u orden de una competencia.
     */
    public function update(Request $request, Competencia $competencia): JsonResponse
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden'       => ['nullable', 'integer', 'min:0'],
        ]);

        $competencia->update([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'orden'       => $data['orden'] ?? $competencia->orden,
        ]);

        return response()->json([
            'message' => 'Competencia actualizada.',
            'data'    => $this->present($competencia),
        ]);
    }

    /**
     * DELETE /competencias/{competencia} — soft delete.
     */
    public function destroy(Competencia $competencia): JsonResponse
    {
        $competencia->delete();

        return response()->json([
            'message' => 'Competencia eliminada.',
        ]);
    }

    /* ──────────────── helpers ──────────────── */

    private function present(Competencia $c): array
    {
        return [
            'id'          => (int) $c->id,
            'curso_id'    => (int) $c->curso_id,
            'nombre'      => $c->nombre,
            'descripcion' => $c->descripcion,
            'orden'       => (int) $c->orden,
        ];
    }
}
