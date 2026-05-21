<?php

namespace App\Modules\Personas\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Stub creado por Persona C porque A no lo tenía.
 * Cada docente se materializa como Usuario(rol=docente) + Docente.
 */
class DocenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('docentes as d')
            ->join('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->whereNull('u.deleted_at')
            ->select([
                'd.id',
                'd.codigo_docente',
                'd.especialidad',
                'd.grado_academico',
                'u.nombres',
                'u.apellidos',
                'u.email',
                'u.dni',
                'u.telefono',
                'u.estado',
                'd.created_at',
            ])
            ->orderByDesc('d.created_at');

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('u.nombres', 'like', "%{$q}%")
                  ->orWhere('u.apellidos', 'like', "%{$q}%")
                  ->orWhere('u.email', 'like', "%{$q}%")
                  ->orWhere('d.codigo_docente', 'like', "%{$q}%");
            });
        }

        $items = $query->get()->map(fn ($r) => [
            'id'              => (int) $r->id,
            'codigo_docente'  => $r->codigo_docente,
            'nombre_completo' => trim($r->nombres . ' ' . $r->apellidos),
            'email'           => $r->email,
            'dni'             => $r->dni,
            'telefono'        => $r->telefono,
            'especialidad'    => $r->especialidad,
            'grado_academico' => $r->grado_academico,
            'estado'          => $r->estado,
        ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombres'         => ['required', 'string', 'max:100'],
            'apellidos'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')],
            'password'        => ['required', 'string', 'min:6'],
            'dni'             => ['nullable', 'string', 'size:8'],
            'telefono'        => ['nullable', 'string', 'max:20'],
            'especialidad'    => ['nullable', 'string', 'max:150'],
            'grado_academico' => ['nullable', 'string', 'max:100'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $usuario = Usuario::create([
                'nombres'   => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'dni'       => $data['dni'] ?? null,
                'telefono'  => $data['telefono'] ?? null,
                'rol'       => 'docente',
                'estado'    => 'activo',
            ]);

            $docente = Docente::create([
                'usuario_id'      => $usuario->id,
                'codigo_docente'  => $this->generarCodigo(),
                'especialidad'    => $data['especialidad'] ?? null,
                'grado_academico' => $data['grado_academico'] ?? null,
            ]);

            return ['usuario' => $usuario, 'docente' => $docente];
        });

        return response()->json([
            'message' => 'Docente creado.',
            'data'    => $this->present($result['docente']->load('usuario')),
        ], 201);
    }

    public function show(Docente $docente): JsonResponse
    {
        $docente->load('usuario');
        return response()->json(['data' => $this->present($docente)]);
    }

    public function update(Request $request, Docente $docente): JsonResponse
    {
        $docente->load('usuario');
        $usuarioId = $docente->usuario->id;

        $data = $request->validate([
            'nombres'         => ['required', 'string', 'max:100'],
            'apellidos'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuarioId)],
            'password'        => ['nullable', 'string', 'min:6'],
            'dni'             => ['nullable', 'string', 'size:8', Rule::unique('usuarios', 'dni')->ignore($usuarioId)],
            'telefono'        => ['nullable', 'string', 'max:20'],
            'especialidad'    => ['nullable', 'string', 'max:150'],
            'grado_academico' => ['nullable', 'string', 'max:100'],
            'estado'          => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        DB::transaction(function () use ($data, $docente) {
            $camposUsuario = [
                'nombres'   => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email'     => $data['email'],
                'dni'       => $data['dni']      ?? null,
                'telefono'  => $data['telefono'] ?? null,
                'estado'    => $data['estado']   ?? $docente->usuario->estado,
            ];
            if (! empty($data['password'])) {
                $camposUsuario['password'] = Hash::make($data['password']);
            }
            $docente->usuario->update($camposUsuario);

            $docente->update([
                'especialidad'    => $data['especialidad']    ?? null,
                'grado_academico' => $data['grado_academico'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Docente actualizado.',
            'data'    => $this->present($docente->fresh()->load('usuario')),
        ]);
    }

    public function destroy(Docente $docente): JsonResponse
    {
        $docente->load('usuario');

        // Soft delete del Usuario (deja el registro Docente con historial)
        DB::transaction(function () use ($docente) {
            $docente->usuario->delete();
        });

        return response()->json(['message' => 'Docente eliminado.']);
    }

    private function present(Docente $d): array
    {
        return [
            'id'              => (int) $d->id,
            'codigo_docente'  => $d->codigo_docente,
            'nombre_completo' => trim(($d->usuario->nombres ?? '') . ' ' . ($d->usuario->apellidos ?? '')),
            'nombres'         => $d->usuario->nombres   ?? '',
            'apellidos'       => $d->usuario->apellidos ?? '',
            'email'           => $d->usuario->email     ?? '',
            'dni'             => $d->usuario->dni       ?? null,
            'telefono'        => $d->usuario->telefono  ?? null,
            'especialidad'    => $d->especialidad,
            'grado_academico' => $d->grado_academico,
            'estado'          => $d->usuario->estado    ?? 'activo',
        ];
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'DOC-' . strtoupper(Str::random(6));
        } while (Docente::where('codigo_docente', $codigo)->exists());
        return $codigo;
    }
}
