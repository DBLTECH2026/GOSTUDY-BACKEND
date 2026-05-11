<?php

namespace App\Modules\Pagos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Modules\Pagos\Requests\AnularPagoRequest;
use App\Modules\Pagos\Requests\RegistrarPagoRequest;
use App\Modules\Pagos\Resources\PagoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    /**
     * GET /api/v1/pagos?matricula_id=&estado=&mes=
     * Devuelve pagos con datos del alumno embebidos (para el listado admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('pagos as p')
            ->join('matriculas as m', 'm.id', '=', 'p.matricula_id')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->whereNull('m.deleted_at')
            ->select([
                'p.id', 'p.matricula_id', 'p.concepto', 'p.descripcion', 'p.monto',
                'p.mes', 'p.fecha_vencimiento', 'p.fecha_pago', 'p.metodo', 'p.estado',
                'p.comprobante_url', 'p.observaciones', 'p.registrado_por', 'p.created_at',
                'e.id as alumno_id', 'e.nombres as alumno_nombres', 'e.apellidos as alumno_apellidos',
                'g.nombre as grado', 'n.nombre as nivel', 's.nombre as seccion',
            ])
            ->orderBy('p.fecha_vencimiento');

        if ($request->filled('matricula_id')) {
            $query->where('p.matricula_id', $request->integer('matricula_id'));
        }
        if ($request->filled('estado')) {
            $query->where('p.estado', $request->string('estado'));
        }
        if ($request->filled('mes')) {
            $query->where('p.mes', $request->integer('mes'));
        }

        $items = $query->get()->map(fn ($r) => [
            'id'                => (int) $r->id,
            'matricula_id'      => (int) $r->matricula_id,
            'concepto'          => $r->concepto,
            'descripcion'       => $r->descripcion,
            'monto'             => (float) $r->monto,
            'mes'               => $r->mes !== null ? (int) $r->mes : null,
            'fecha_vencimiento' => $r->fecha_vencimiento,
            'fecha_pago'        => $r->fecha_pago,
            'metodo'            => $r->metodo,
            'estado'            => $r->estado,
            'comprobante_url'   => $r->comprobante_url
                ? (str_starts_with($r->comprobante_url, 'http')
                    ? $r->comprobante_url
                    : asset('storage/' . $r->comprobante_url))
                : null,
            'observaciones'     => $r->observaciones,
            'registrado_por'    => $r->registrado_por !== null ? (int) $r->registrado_por : null,
            'created_at'        => $r->created_at,
            'alumno' => [
                'id'        => (int) $r->alumno_id,
                'nombres'   => $r->alumno_nombres,
                'apellidos' => $r->alumno_apellidos,
                'grado'     => "{$r->grado} {$r->nivel}",
                'seccion'   => $r->seccion,
            ],
        ]);

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/v1/pagos/{pago}/registrar
     */
    public function registrar(RegistrarPagoRequest $request, Pago $pago): JsonResponse
    {
        if (! $pago->estaPendienteOVencido()) {
            return response()->json([
                'message' => 'El pago no se puede registrar: estado actual ' . $pago->estado,
            ], 422);
        }

        $comprobanteUrl = null;
        if ($request->hasFile('comprobante')) {
            $comprobanteUrl = $request->file('comprobante')
                ->store('comprobantes', 'public');
        }

        $pago->update([
            'estado'          => Pago::ESTADO_PAGADO,
            'metodo'          => $request->string('metodo'),
            'monto'           => $request->float('monto'),
            'fecha_pago'      => Carbon::now()->toDateString(),
            'observaciones'   => $request->input('observaciones'),
            'comprobante_url' => $comprobanteUrl ?? $pago->comprobante_url,
            'registrado_por'  => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Pago registrado correctamente.',
            'data'    => new PagoResource($pago),
        ]);
    }

    /**
     * POST /api/v1/pagos/{pago}/anular
     */
    public function anular(AnularPagoRequest $request, Pago $pago): JsonResponse
    {
        if ($pago->estado === Pago::ESTADO_ANULADO) {
            return response()->json([
                'message' => 'Este pago ya está anulado.',
            ], 422);
        }

        $pago->update([
            'estado'        => Pago::ESTADO_ANULADO,
            'observaciones' => trim(($pago->observaciones ?? '') . "\n[Anulado] " . $request->string('motivo')),
        ]);

        return response()->json([
            'message' => 'Pago anulado.',
            'data'    => new PagoResource($pago),
        ]);
    }
}
