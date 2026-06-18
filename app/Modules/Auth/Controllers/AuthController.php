<?php

namespace App\Modules\Auth\Controllers;

use App\Models\Estudiante;
use App\Models\Usuario;
use App\Modules\Auth\Requests\AdminLoginRequest;
use App\Modules\Auth\Requests\AdminRegisterRequest;
use App\Modules\Auth\Requests\PortalLoginRequest;
use App\Modules\Auth\Requests\PortalRegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController
{
    /* ───────────────────── ADMIN / DOCENTE ───────────────────── */

    public function registerAdmin(AdminRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $usuario = Usuario::create([
            'nombres'   => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'dni'       => $data['dni']      ?? null,
            'telefono'  => $data['telefono'] ?? null,
            'rol'       => $data['rol']      ?? 'admin',
            'estado'    => 'activo',
        ]);

        $token = $usuario->createToken('admin', ['admin'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->presentAdmin($usuario),
        ], 201);
    }

    public function loginAdmin(AdminLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $usuario = Usuario::where('email', $data['email'])->first();

        if (! $usuario || ! Hash::check($data['password'], $usuario->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 422);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json([
                'message' => 'Cuenta inactiva. Contacta al administrador.',
            ], 403);
        }

        $token = $usuario->createToken('admin', ['admin'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->presentAdmin($usuario),
        ]);
    }

    /* ───────────────────── ESTUDIANTE (PORTAL) ───────────────────── */

    public function registerPortal(PortalRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $estudiante = DB::transaction(function () use ($data) {
            return Estudiante::create([
                'codigo_estudiante' => $this->generarCodigoEstudiante(),
                'dni'               => $data['dni'],
                'pin'               => $data['pin'],
                'nombres'           => $data['nombres'],
                'apellidos'         => $data['apellidos'],
                'fecha_nacimiento'  => $data['fecha_nacimiento'],
                'sexo'              => $data['sexo'],
                'direccion'         => $data['direccion'],
                'departamento'      => $data['departamento']     ?? null,
                'provincia'         => $data['provincia']        ?? null,
                'distrito'          => $data['distrito']         ?? null,
                'ie_procedencia'    => $data['ie_procedencia']   ?? null,
                'anio_procedencia'  => $data['anio_procedencia'] ?? null,
                'estado'            => 'activo',
            ]);
        });

        $token = $estudiante->createToken('portal', ['estudiante'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->presentEstudiante($estudiante),
        ], 201);
    }

    public function loginPortal(PortalLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $estudiante = Estudiante::where('dni', $data['dni'])->first();

        if (! $estudiante || ! Hash::check($data['pin'], $estudiante->pin)) {
            return response()->json([
                'message' => 'DNI o PIN incorrecto.',
            ], 422);
        }

        if ($estudiante->estado !== 'activo') {
            return response()->json([
                'message' => 'Estudiante no activo.',
            ], 403);
        }

        $token = $estudiante->createToken('portal', ['estudiante'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->presentEstudiante($estudiante),
        ]);
    }

    /* ───────────────────── COMUNES ───────────────────── */

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Usuario) {
            return response()->json(['user' => $this->presentAdmin($user)]);
        }

        if ($user instanceof Estudiante) {
            return response()->json(['user' => $this->presentEstudiante($user)]);
        }

        return response()->json(['message' => 'No autenticado.'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /* ───────────────────── PERFIL ADMIN ───────────────────── */

    public function updatePerfil(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Usuario) {
            return response()->json(['message' => 'Acción no permitida.'], 403);
        }

        $data = $request->validate([
            'nombres'   => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:160', "unique:usuarios,email,{$user->id}"],
            'dni'       => ['nullable', 'string', 'max:15'],
            'telefono'  => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($data);

        return response()->json(['user' => $this->presentAdmin($user->fresh())]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Usuario) {
            return response()->json(['message' => 'Acción no permitida.'], 403);
        }

        $data = $request->validate([
            'password_actual' => ['required', 'string'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['password_actual'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
                'errors'  => ['password_actual' => ['La contraseña actual es incorrecta.']],
            ], 422);
        }

        $user->update(['password' => $data['password']]);

        return response()->json(['message' => 'Contraseña actualizada.']);
    }

    public function updateFoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Usuario) {
            return response()->json(['message' => 'Acción no permitida.'], 403);
        }

        $request->validate([
            'foto' => ['required', 'image'],
        ]);

        // Borrar foto anterior si vivía en storage/public
        if ($user->foto_url && str_contains($user->foto_url, '/storage/usuarios/fotos/')) {
            $prev = str_replace('/storage/', '', parse_url($user->foto_url, PHP_URL_PATH));
            Storage::disk('public')->delete($prev);
        }

        $path = $request->file('foto')->store('usuarios/fotos', 'public');
        $user->update(['foto_url' => Storage::url($path)]);

        return response()->json(['user' => $this->presentAdmin($user->fresh())]);
    }

    /* ───────────────────── HELPERS ───────────────────── */

    private function presentAdmin(Usuario $u): array
    {
        return [
            'id'        => $u->id,
            'tipo'      => 'admin',
            'rol'       => $u->rol,
            'nombres'   => $u->nombres,
            'apellidos' => $u->apellidos,
            'nombre'    => $u->nombre_completo,
            'email'     => $u->email,
            'dni'       => $u->dni,
            'telefono'  => $u->telefono,
            'foto_url'  => $u->foto_url,
            'estado'    => $u->estado,
        ];
    }

    private function presentEstudiante(Estudiante $e): array
    {
        return [
            'id'                => $e->id,
            'tipo'              => 'estudiante',
            'rol'               => 'estudiante',
            'codigo_estudiante' => $e->codigo_estudiante,
            'dni'               => $e->dni,
            'nombres'           => $e->nombres,
            'apellidos'         => $e->apellidos,
            'nombre'            => $e->nombre_completo,
            'fecha_nacimiento'  => $e->fecha_nacimiento?->toDateString(),
            'sexo'              => $e->sexo,
            'direccion'         => $e->direccion,
            'departamento'      => $e->departamento,
            'provincia'         => $e->provincia,
            'distrito'          => $e->distrito,
            'ie_procedencia'    => $e->ie_procedencia,
            'anio_procedencia'  => $e->anio_procedencia,
            'foto_url'          => $e->foto_url,
            'estado'            => $e->estado,
        ];
    }

    private function generarCodigoEstudiante(): string
    {
        do {
            $codigo = 'EST-' . now()->year . '-' . strtoupper(Str::random(5));
        } while (Estudiante::where('codigo_estudiante', $codigo)->exists());

        return $codigo;
    }
}
