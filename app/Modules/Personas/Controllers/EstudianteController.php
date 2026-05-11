<?php

namespace App\Modules\Personas\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Stub creado por Persona C porque A no lo tenía. Solo index + store.
 * El resto del CRUD lo puede consolidar A cuando termine su módulo.
 */
class EstudianteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Estudiante::query()->orderByDesc('created_at');

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('nombres', 'like', "%{$q}%")
                  ->orWhere('apellidos', 'like', "%{$q}%")
                  ->orWhere('dni', 'like', "%{$q}%")
                  ->orWhere('codigo_estudiante', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->get()->map(fn ($e) => $this->present($e)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dni'              => ['required', 'string', 'size:8', Rule::unique('estudiantes', 'dni')],
            'pin'              => ['required', 'string', 'size:6'],
            'nombres'          => ['required', 'string', 'max:100'],
            'apellidos'        => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo'             => ['required', Rule::in(['M', 'F'])],
            'direccion'        => ['required', 'string', 'max:200'],
            'departamento'     => ['nullable', 'string', 'max:60'],
            'provincia'        => ['nullable', 'string', 'max:60'],
            'distrito'         => ['nullable', 'string', 'max:60'],
            'ie_procedencia'   => ['nullable', 'string', 'max:150'],
            'anio_procedencia' => ['nullable', 'integer', 'min:1990', 'max:2100'],
        ]);

        $estudiante = Estudiante::create([
            'codigo_estudiante' => $this->generarCodigo(),
            'dni'               => $data['dni'],
            'pin'               => Hash::make($data['pin']),
            'nombres'           => $data['nombres'],
            'apellidos'         => $data['apellidos'],
            'fecha_nacimiento'  => $data['fecha_nacimiento'],
            'sexo'              => $data['sexo'],
            'direccion'         => $data['direccion'],
            'departamento'      => $data['departamento'] ?? null,
            'provincia'         => $data['provincia'] ?? null,
            'distrito'          => $data['distrito'] ?? null,
            'ie_procedencia'    => $data['ie_procedencia'] ?? null,
            'anio_procedencia'  => $data['anio_procedencia'] ?? null,
            'estado'            => 'activo',
        ]);

        return response()->json([
            'message' => 'Estudiante creado.',
            'data'    => $this->present($estudiante),
        ], 201);
    }

    private function present(Estudiante $e): array
    {
        return [
            'id'                => $e->id,
            'codigo_estudiante' => $e->codigo_estudiante,
            'dni'               => $e->dni,
            'nombres'           => $e->nombres,
            'apellidos'         => $e->apellidos,
            'nombre_completo'   => trim($e->nombres . ' ' . $e->apellidos),
            'fecha_nacimiento'  => $e->fecha_nacimiento?->toDateString(),
            'sexo'              => $e->sexo,
            'direccion'         => $e->direccion,
            'distrito'          => $e->distrito,
            'estado'            => $e->estado,
            'created_at'        => $e->created_at?->toIso8601String(),
        ];
    }

    private function generarCodigo(): string
    {
        $anio = now()->year;
        do {
            $codigo = 'EST-' . $anio . '-' . strtoupper(Str::random(5));
        } while (Estudiante::where('codigo_estudiante', $codigo)->exists());
        return $codigo;
    }
}
