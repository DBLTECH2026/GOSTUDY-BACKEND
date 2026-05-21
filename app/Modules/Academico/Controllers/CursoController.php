<?php

namespace App\Modules\Academico\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Grado;
use App\Models\Nivel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CursoController extends Controller
{
    /**
     * GET /cursos?nivel_id=X&grado_id=Y&q=Z
     * Devuelve cursos activos con conteo de secciones que los dictan.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Curso::query()
            ->with(['grado.nivel'])
            ->withCount('secciones');

        if ($nivelId = $request->query('nivel_id')) {
            $query->whereHas('grado', fn ($q) => $q->where('nivel_id', $nivelId));
        }
        if ($gradoId = $request->query('grado_id')) {
            $query->where('grado_id', $gradoId);
        }
        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                  ->orWhere('codigo', 'like', "%{$q}%");
            });
        }

        $cursos = $query->orderBy('grado_id')->orderBy('nombre')->get()
            ->map(fn (Curso $c) => $this->present($c));

        return response()->json(['data' => $cursos]);
    }

    /**
     * POST /cursos
     * Modo único: { grado_id, nombre, horas_semana, codigo?, descripcion? }
     * Modo lote:  { nivel_id, modo_lote: true, nombre, horas_semana, descripcion? }
     */
    public function store(Request $request): JsonResponse
    {
        $modoLote = (bool) $request->input('modo_lote', false);

        if ($modoLote) {
            $data = $request->validate([
                'nivel_id'     => ['required', 'integer', Rule::exists('niveles', 'id')],
                'nombre'       => ['required', 'string', 'max:100'],
                'horas_semana' => ['required', 'integer', 'min:1', 'max:15'],
                'descripcion'  => ['nullable', 'string'],
            ]);

            $nivel = Nivel::find($data['nivel_id']);
            $grados = Grado::where('nivel_id', $nivel->id)->orderBy('orden')->get();

            $creados = DB::transaction(function () use ($data, $nivel, $grados) {
                $out = [];
                foreach ($grados as $grado) {
                    $codigo = $this->generarCodigo($nivel->nombre, $grado->nombre, $data['nombre']);
                    // Si el código ya existe (curso eliminado con soft delete o repetido),
                    // le agregamos un sufijo aleatorio para mantener unicidad.
                    $codigoFinal = $this->codigoUnico($codigo);

                    $out[] = Curso::create([
                        'grado_id'     => $grado->id,
                        'nombre'       => $data['nombre'],
                        'codigo'       => $codigoFinal,
                        'horas_semana' => $data['horas_semana'],
                        'descripcion'  => $data['descripcion'] ?? null,
                    ])->load('grado.nivel');
                }
                return $out;
            });

            return response()->json([
                'message' => count($creados) . " cursos creados (uno por grado de {$nivel->nombre}).",
                'data'    => [
                    'creados' => count($creados),
                    'cursos'  => array_map(fn ($c) => $this->present($c->loadCount('secciones')), $creados),
                ],
            ], 201);
        }

        // Modo único
        $data = $request->validate([
            'grado_id'     => ['required', 'integer', Rule::exists('grados', 'id')],
            'nombre'       => ['required', 'string', 'max:100'],
            'codigo'       => ['nullable', 'string', 'max:20', Rule::unique('cursos', 'codigo')->whereNull('deleted_at')],
            'horas_semana' => ['required', 'integer', 'min:1', 'max:15'],
            'descripcion'  => ['nullable', 'string'],
        ]);

        $grado = Grado::with('nivel')->find($data['grado_id']);
        $codigo = $data['codigo'] ?? $this->generarCodigo($grado->nivel->nombre, $grado->nombre, $data['nombre']);
        $codigo = $this->codigoUnico($codigo);

        $curso = Curso::create([
            'grado_id'     => $data['grado_id'],
            'nombre'       => $data['nombre'],
            'codigo'       => $codigo,
            'horas_semana' => $data['horas_semana'],
            'descripcion'  => $data['descripcion'] ?? null,
        ])->load('grado.nivel')->loadCount('secciones');

        return response()->json([
            'message' => 'Curso creado.',
            'data'    => $this->present($curso),
        ], 201);
    }

    public function show(Curso $curso): JsonResponse
    {
        $curso->load('grado.nivel')->loadCount('secciones');
        return response()->json(['data' => $this->present($curso)]);
    }

    public function update(Request $request, Curso $curso): JsonResponse
    {
        $data = $request->validate([
            'grado_id'     => ['required', 'integer', Rule::exists('grados', 'id')],
            'nombre'       => ['required', 'string', 'max:100'],
            'codigo'       => ['required', 'string', 'max:20', Rule::unique('cursos', 'codigo')->ignore($curso->id)->whereNull('deleted_at')],
            'horas_semana' => ['required', 'integer', 'min:1', 'max:15'],
            'descripcion'  => ['nullable', 'string'],
        ]);

        $curso->update($data);
        $curso->load('grado.nivel')->loadCount('secciones');

        return response()->json([
            'message' => 'Curso actualizado.',
            'data'    => $this->present($curso),
        ]);
    }

    /**
     * DELETE /cursos/{id} — soft delete.
     * El curso desaparece de listados pero se conservan las asignaciones
     * en seccion_curso para mantener historial.
     */
    public function destroy(Curso $curso): JsonResponse
    {
        $asignadas = $curso->secciones()->count();
        $curso->delete();

        return response()->json([
            'message' => $asignadas > 0
                ? "Curso eliminado. Las {$asignadas} asignaciones existentes se conservaron por historial."
                : 'Curso eliminado.',
        ]);
    }

    /* ──────────────── helpers ──────────────── */

    private function present(Curso $c): array
    {
        return [
            'id'                  => (int) $c->id,
            'nombre'              => $c->nombre,
            'codigo'              => $c->codigo,
            'horas_semana'        => (int) $c->horas_semana,
            'descripcion'         => $c->descripcion,
            'grado_id'            => (int) $c->grado_id,
            'grado'               => $c->grado?->nombre,
            'nivel_id'            => $c->grado?->nivel_id ? (int) $c->grado->nivel_id : null,
            'nivel'               => $c->grado?->nivel?->nombre,
            'secciones_asignadas' => (int) ($c->secciones_count ?? 0),
        ];
    }

    private function generarCodigo(string $nivel, string $grado, string $curso): string
    {
        $prefNivel  = strtoupper(substr($nivel, 0, 3));
        $cleanGrado = Str::slug(str_replace(' ', '-', $grado));

        $clean = preg_replace('/[^A-Za-z ]/', '', Str::ascii($curso));
        $iniciales = strtoupper(implode('', array_map(
            fn ($w) => substr($w, 0, 1),
            preg_split('/\s+/', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        )));
        $sufijo = strtoupper(substr(str_replace(' ', '', $clean), 0, 3));

        return "{$prefNivel}-{$cleanGrado}-{$iniciales}{$sufijo}";
    }

    /** Asegura que un código sea único en la tabla (entre los no-soft-deleted). */
    private function codigoUnico(string $codigo): string
    {
        if (! Curso::where('codigo', $codigo)->exists()) return $codigo;

        $i = 2;
        do {
            $candidato = "{$codigo}-{$i}";
            $i++;
        } while (Curso::where('codigo', $candidato)->exists());
        return $candidato;
    }
}
