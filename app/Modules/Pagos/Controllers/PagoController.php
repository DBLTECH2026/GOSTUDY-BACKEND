<?php

namespace App\Modules\Pagos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Modules\Pagos\Requests\AnularPagoRequest;
use App\Modules\Pagos\Requests\RegistrarPagoRequest;
use App\Modules\Pagos\Resources\PagoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class PagoController extends Controller
{
    /**
     * GET /api/v1/pagos?matricula_id=&estado=&mes=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Pago::query()->latest('fecha_vencimiento');

        if ($request->filled('matricula_id')) {
            $query->where('matricula_id', $request->integer('matricula_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('mes')) {
            $query->where('mes', $request->integer('mes'));
        }

        $pagos = $query->paginate(50);

        return PagoResource::collection($pagos);
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
