<?php

namespace App\Modules\Pagos\Resources;

use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pago
 */
class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'matricula_id'      => $this->matricula_id,
            'concepto'          => $this->concepto,
            'descripcion'       => $this->descripcion,
            'monto'             => (float) $this->monto,
            'mes'               => $this->mes,
            'fecha_vencimiento' => $this->fecha_vencimiento?->toDateString(),
            'fecha_pago'        => $this->fecha_pago?->toDateString(),
            'metodo'            => $this->metodo,
            'estado'            => $this->estado,
            'comprobante_url'   => $this->comprobante_url
                ? (str_starts_with($this->comprobante_url, 'http')
                    ? $this->comprobante_url
                    : asset('storage/' . $this->comprobante_url))
                : null,
            'observaciones'     => $this->observaciones,
            'registrado_por'    => $this->registrado_por,
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
