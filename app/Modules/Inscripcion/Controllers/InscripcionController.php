<?php

namespace App\Modules\Inscripcion\Controllers;

use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\PerfilFamiliar;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Modules\Inscripcion\Requests\StoreInscripcionRequest;
use App\Modules\Matricula\Events\MatriculaAprobada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InscripcionController
{
    /* ───────────────────── Público ───────────────────── */

    public function store(StoreInscripcionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // El periodo activo se infiere automáticamente; no se pide al padre.
        $periodo = PeriodoAcademico::activo();
        if (! $periodo) {
            return response()->json([
                'message' => 'No hay periodo académico activo. Contacta a administración.',
            ], 422);
        }

        // Validar que no haya ya una inscripción pendiente con el mismo DNI en ese periodo
        $duplicada = Inscripcion::where('dni_estudiante', $data['dni_estudiante'])
            ->where('periodo_id', $periodo->id)
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->exists();
        if ($duplicada) {
            return response()->json([
                'message' => 'Ya existe una inscripción registrada con ese DNI para este periodo.',
            ], 422);
        }

        // Guardar archivos en disco 'public' si vienen
        $comprobantePath = $request->hasFile('comprobante_pago')
            ? $request->file('comprobante_pago')->store('inscripciones/comprobantes', 'public')
            : null;
        $certificadoPath = $request->hasFile('certificado_estudios')
            ? $request->file('certificado_estudios')->store('inscripciones/certificados', 'public')
            : null;

        $inscripcion = DB::transaction(function () use ($data, $periodo, $request, $comprobantePath, $certificadoPath) {
            return Inscripcion::create([
                'codigo_inscripcion'   => $this->generarCodigoInscripcion(),
                'periodo_id'           => $periodo->id,
                'nivel_id'             => $data['nivel_id'],
                'grado_id'             => $data['grado_id'],
                'dni_estudiante'       => $data['dni_estudiante'],
                'nombres_estudiante'   => $data['nombres_estudiante'],
                'apellidos_estudiante' => $data['apellidos_estudiante'],
                'fecha_nacimiento'     => $data['fecha_nacimiento'],
                'sexo'                 => $data['sexo'],
                'direccion'            => $data['direccion'],
                'departamento'         => $data['departamento'] ?? null,
                'provincia'            => $data['provincia'] ?? null,
                'distrito'             => $data['distrito'] ?? null,
                'ie_procedencia'       => $data['ie_procedencia'] ?? null,
                'anio_procedencia'     => $data['anio_procedencia'] ?? null,
                'pin_hash'             => Hash::make($data['pin']),
                'datos_familiares'     => [
                    'apoderado_principal' => [
                        'tipo'      => $data['apoderado_tipo']     ?? 'apoderado',
                        'nombres'   => $data['apoderado_nombres'],
                        'apellidos' => $data['apoderado_apellidos'],
                        'dni'       => $data['apoderado_dni'],
                        'telefono'  => $data['apoderado_telefono'] ?? null,
                        'email'     => $data['apoderado_email']    ?? null,
                    ],
                ],
                'comprobante_pago_url'     => $comprobantePath,
                'certificado_estudios_url' => $certificadoPath,
                'estado'                   => 'pendiente',
                'ip_origen'                => $request->ip(),
                'fecha_inscripcion'        => now(),
            ]);
        });

        // Email opcional (best-effort, no rompe si SMTP no está configurado)
        if (! empty($data['apoderado_email'])) {
            $this->enviarEmailFacturacion(
                $data['apoderado_email'],
                $data['nombres_estudiante'] . ' ' . $data['apellidos_estudiante'],
                $inscripcion->codigo_inscripcion,
            );
        }

        return response()->json([
            'message' => 'Inscripción recibida. Quedará pendiente de revisión por administración.',
            'data'    => $this->present($inscripcion),
        ], 201);
    }

    /**
     * POST /api/v1/inscripcion/enviar-facturacion  (público)
     * Botón del paso 2 del form: envía al email del apoderado el monto
     * y los datos de pago, sin completar todavía la inscripción.
     */
    public function enviarFacturacion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'           => ['required', 'email'],
            'nombre_alumno'   => ['required', 'string', 'max:200'],
        ]);

        $this->enviarEmailFacturacion($data['email'], $data['nombre_alumno'], null);

        return response()->json([
            'message' => "Enviamos la información de pago a {$data['email']}.",
        ]);
    }

    private function enviarEmailFacturacion(string $email, string $alumno, ?string $codigoInscripcion): void
    {
        $monto = number_format(\App\Modules\Pagos\Services\CrearPagosService::MONTO_MATRICULA, 2);

        $datos = [
            'alumno' => $alumno,
            'monto'  => $monto,
            'codigo' => $codigoInscripcion,
        ];

        try {
            Mail::send('emails.facturacion', $datos, function ($m) use ($email) {
                $m->to($email)->subject('Trilce — Datos de pago de matrícula');
            });
        } catch (\Throwable $e) {
            Log::info('[Inscripcion] Email no enviado, fallback a log', [
                'email' => $email,
                'datos' => $datos,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ───────────────────── Admin ───────────────────── */

    public function index(Request $request): JsonResponse
    {
        $estado = $request->query('estado'); // pendiente | aprobada | rechazada | null

        $query = Inscripcion::with(['nivel', 'grado', 'periodo'])
            ->orderByDesc('fecha_inscripcion');

        if ($estado) {
            $query->where('estado', $estado);
        }

        $items = $query->get()->map(fn ($i) => $this->present($i));

        return response()->json(['data' => $items]);
    }

    public function show(Inscripcion $inscripcion): JsonResponse
    {
        $inscripcion->load(['nivel', 'grado', 'periodo', 'aprobadoPor']);
        return response()->json(['data' => $this->present($inscripcion, detalle: true)]);
    }

    public function aprobar(Request $request, Inscripcion $inscripcion): JsonResponse
    {
        if ($inscripcion->estado !== 'pendiente') {
            return response()->json([
                'message' => 'Esta inscripción ya fue procesada.',
            ], 422);
        }

        // Si ya existe un estudiante con ese DNI, bloquear
        if (Estudiante::where('dni', $inscripcion->dni_estudiante)->exists()) {
            return response()->json([
                'message' => 'Ya existe un estudiante registrado con ese DNI.',
            ], 422);
        }

        // Buscar sección con cupo en el grado del periodo (auto-asignación)
        $seccion = Seccion::where('grado_id', $inscripcion->grado_id)
            ->where('periodo_id', $inscripcion->periodo_id)
            ->get()
            ->first(fn (Seccion $s) => $s->cupoDisponible() > 0);

        if (! $seccion) {
            return response()->json([
                'message' => 'No hay secciones con cupo disponible para este grado. Crea una sección desde el panel de catálogos.',
            ], 422);
        }

        $matricula = DB::transaction(function () use ($request, $inscripcion, $seccion) {
            // El cast 'pin' => 'hashed' detecta valores ya hasheados (Hash::isHashed)
            // y NO rehashea, por lo que pasamos directamente el pin_hash.
            $estudiante = Estudiante::create([
                'codigo_estudiante' => $this->generarCodigoEstudiante(),
                'dni'               => $inscripcion->dni_estudiante,
                'pin'               => $inscripcion->pin_hash,
                'nombres'           => $inscripcion->nombres_estudiante,
                'apellidos'         => $inscripcion->apellidos_estudiante,
                'fecha_nacimiento'  => $inscripcion->fecha_nacimiento,
                'sexo'              => $inscripcion->sexo,
                'direccion'         => $inscripcion->direccion,
                'departamento'     => $inscripcion->departamento,
                'provincia'         => $inscripcion->provincia,
                'distrito'          => $inscripcion->distrito,
                'ie_procedencia'    => $inscripcion->ie_procedencia,
                'anio_procedencia'  => $inscripcion->anio_procedencia,
                'estado'            => 'activo',
            ]);

            // Apoderado principal desde datos_familiares
            $apoderado = $inscripcion->datos_familiares['apoderado_principal'] ?? null;
            if ($apoderado) {
                PerfilFamiliar::create([
                    'estudiante_id' => $estudiante->id,
                    'tipo'          => $apoderado['tipo'] ?? 'apoderado',
                    'nombres'       => $apoderado['nombres'] ?? '',
                    'apellidos'     => $apoderado['apellidos'] ?? '',
                    'dni'           => $apoderado['dni'] ?? null,
                    'telefono'      => $apoderado['telefono'] ?? null,
                    'email'         => $apoderado['email'] ?? null,
                    'es_titular'    => true,
                    'vive_con'      => true,
                ]);
            }

            // Crear la matrícula activa (la asignación a sección la hizo
            // auto-asignación; un wizard de matrícula podría reemplazarla más adelante)
            $matricula = Matricula::create([
                'estudiante_id'   => $estudiante->id,
                'periodo_id'      => $inscripcion->periodo_id,
                'seccion_id'      => $seccion->id,
                'inscripcion_id'  => $inscripcion->id,
                'fecha_matricula' => now()->toDateString(),
                'estado'          => Matricula::ESTADO_ACTIVA,
                'registrado_por'  => $request->user()?->id,
            ]);

            $inscripcion->update([
                'estado'       => 'aprobada',
                'aprobada_por' => $request->user()?->id,
                'aprobada_en'  => now(),
            ]);

            return $matricula;
        });

        // Dispatch del evento ya FUERA de la transacción, para que el
        // listener (Persona C) genere los pagos sólo si todo lo anterior
        // se confirmó en BD.
        MatriculaAprobada::dispatch($matricula);

        // El admin ya validó el comprobante en /inscripciones antes de aprobar,
        // así que el pago de matrícula se crea directamente como PAGADO.
        if ($inscripcion->comprobante_pago_url) {
            $pagoMatricula = Pago::where('matricula_id', $matricula->id)
                ->where('concepto', Pago::CONCEPTO_MATRICULA)
                ->first();
            if ($pagoMatricula) {
                $pagoMatricula->update([
                    'estado'          => Pago::ESTADO_PAGADO,
                    'metodo'          => 'transferencia',
                    'fecha_pago'      => now()->toDateString(),
                    'comprobante_url' => $inscripcion->comprobante_pago_url,
                    'observaciones'   => 'Pago verificado al aprobar inscripción '
                        . $inscripcion->codigo_inscripcion . '.',
                    'registrado_por'  => $request->user()?->id,
                ]);
            }
        }

        $inscripcion->refresh()->load(['nivel', 'grado', 'periodo']);

        // Email al apoderado: inscripción aprobada con datos del alumno y matrícula
        $emailApoderado = $inscripcion->datos_familiares['apoderado_principal']['email'] ?? null;
        if ($emailApoderado) {
            $this->enviarEmailAprobacion($emailApoderado, $inscripcion, $matricula, $seccion);
        }

        return response()->json([
            'message' => 'Inscripción aprobada. Estudiante creado y matrícula generada (sección ' . $seccion->nombre . '). Los pagos del periodo se generaron automáticamente.',
            'data'    => $this->present($inscripcion),
        ]);
    }

    private function enviarEmailAprobacion(string $email, Inscripcion $inscripcion, Matricula $matricula, Seccion $seccion): void
    {
        $apoderado = $inscripcion->datos_familiares['apoderado_principal'] ?? [];

        $datos = [
            'alumno_nombres'      => $inscripcion->nombres_estudiante,
            'alumno_apellidos'    => $inscripcion->apellidos_estudiante,
            'apoderado_nombres'   => trim(($apoderado['nombres'] ?? '') . ' ' . ($apoderado['apellidos'] ?? '')),
            'codigo_inscripcion'  => $inscripcion->codigo_inscripcion,
            'codigo_estudiante'   => $matricula->estudiante->codigo_estudiante ?? '',
            'nivel'               => $inscripcion->nivel->nombre ?? '',
            'grado'               => $inscripcion->grado->nombre ?? '',
            'seccion'             => $seccion->nombre,
            'periodo'             => $inscripcion->periodo->anio ?? date('Y'),
            'fecha_matricula'     => $matricula->fecha_matricula?->format('d/m/Y'),
            'pin_acceso'          => $inscripcion->dni_estudiante, // El alumno usa su DNI como user, PIN ya lo creó
        ];

        try {
            Mail::send('emails.aprobacion', $datos, function ($m) use ($email, $inscripcion) {
                $m->to($email)->subject('🎉 Inscripción aprobada — '
                    . $inscripcion->nombres_estudiante . ' ' . $inscripcion->apellidos_estudiante);
            });
        } catch (\Throwable $e) {
            Log::info('[Inscripcion] Email aprobación no enviado', [
                'email' => $email,
                'datos' => $datos,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function rechazar(Request $request, Inscripcion $inscripcion): JsonResponse
    {
        if ($inscripcion->estado !== 'pendiente') {
            return response()->json([
                'message' => 'Esta inscripción ya fue procesada.',
            ], 422);
        }

        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        $inscripcion->update([
            'estado'         => 'rechazada',
            'motivo_rechazo' => $validated['motivo'] ?? null,
            'aprobada_por'   => $request->user()?->id,
            'aprobada_en'    => now(),
        ]);

        return response()->json([
            'message' => 'Inscripción rechazada.',
            'data'    => $this->present($inscripcion->fresh()->load(['nivel', 'grado'])),
        ]);
    }

    /* ───────────────────── Helpers ───────────────────── */

    private function present(Inscripcion $i, bool $detalle = false): array
    {
        return [
            'id'                  => $i->id,
            'codigo'              => $i->codigo_inscripcion,
            'estado'              => $i->estado,
            'periodo'             => $i->periodo?->descripcion,
            'nivel'               => $i->nivel?->nombre,
            'grado'               => $i->grado?->nombre,
            'dni'                 => $i->dni_estudiante,
            'nombres'             => $i->nombres_estudiante,
            'apellidos'           => $i->apellidos_estudiante,
            'nombre_completo'     => trim($i->nombres_estudiante . ' ' . $i->apellidos_estudiante),
            'fecha_nacimiento'    => $i->fecha_nacimiento?->toDateString(),
            'sexo'                => $i->sexo,
            'direccion'           => $i->direccion,
            'departamento'        => $i->departamento,
            'provincia'           => $i->provincia,
            'distrito'            => $i->distrito,
            'ie_procedencia'      => $i->ie_procedencia,
            'anio_procedencia'    => $i->anio_procedencia,
            'apoderado'           => $i->datos_familiares['apoderado_principal'] ?? null,
            'motivo_rechazo'      => $i->motivo_rechazo,
            'fecha_inscripcion'   => $i->fecha_inscripcion?->toDateTimeString(),
            'aprobada_en'         => $i->aprobada_en?->toDateTimeString(),
            'comprobante_pago_url' => $i->comprobante_pago_url
                ? asset('storage/' . $i->comprobante_pago_url)
                : null,
            'certificado_estudios_url' => $i->certificado_estudios_url
                ? asset('storage/' . $i->certificado_estudios_url)
                : null,
        ] + ($detalle ? [
            'datos_familiares' => $i->datos_familiares,
        ] : []);
    }

    private function generarCodigoInscripcion(): string
    {
        $anio = now()->year;
        do {
            $codigo = 'INS-' . $anio . '-' . strtoupper(Str::random(5));
        } while (Inscripcion::where('codigo_inscripcion', $codigo)->exists());
        return $codigo;
    }

    private function generarCodigoEstudiante(): string
    {
        $anio = now()->year;
        do {
            $codigo = 'EST-' . $anio . '-' . strtoupper(Str::random(5));
        } while (Estudiante::where('codigo_estudiante', $codigo)->exists());
        return $codigo;
    }
}
