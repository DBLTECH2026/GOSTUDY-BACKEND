<?php

namespace App\Modules\Pagos\Resources;

use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Estado de cuenta de un estudiante: agrupa todos sus pagos del periodo
 * activo con totales por estado.
 *
 * Espera recibir en `$this->resource` un array u objeto con:
 *   - estudiante (datos del alumno: id, nombres, codigo)
 *   - pagos (Collection<Pago>)
 */
class EstadoCuentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Collection<int, Pago> $pagos */
        $pagos = $this->resource['pagos'] ?? collect();

        $totalFacturado = (float) $pagos->sum('monto');
        $totalPagado    = (float) $pagos->where('estado', Pago::ESTADO_PAGADO)->sum('monto');
        $totalPendiente = (float) $pagos->where('estado', Pago::ESTADO_PENDIENTE)->sum('monto');
        $totalVencido   = (float) $pagos->where('estado', Pago::ESTADO_VENCIDO)->sum('monto');

        $cobranza = $totalFacturado > 0
            ? round(($totalPagado / $totalFacturado) * 100, 1)
            : 0.0;

        return [
            'estudiante' => $this->resource['estudiante'] ?? null,
            'totales'    => [
                'facturado' => $totalFacturado,
                'pagado'    => $totalPagado,
                'pendiente' => $totalPendiente,
                'vencido'   => $totalVencido,
                'cobranza_porcentaje' => $cobranza,
            ],
            'pagos' => PagoResource::collection($pagos),
        ];
    }
}
