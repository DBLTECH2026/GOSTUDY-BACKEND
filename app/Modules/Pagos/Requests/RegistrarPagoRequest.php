<?php

namespace App\Modules\Pagos\Requests;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $metodos = [
            Pago::METODO_EFECTIVO,
            Pago::METODO_TRANSFERENCIA,
            Pago::METODO_YAPE,
            Pago::METODO_PLIN,
            Pago::METODO_OTRO,
        ];

        return [
            'metodo'        => ['required', 'string', 'in:' . implode(',', $metodos)],
            'monto'         => ['required', 'numeric', 'min:0.01'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'comprobante'   => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'metodo.required'    => 'El método de pago es obligatorio.',
            'metodo.in'          => 'El método de pago no es válido.',
            'monto.required'     => 'Indica el monto recibido.',
            'monto.min'          => 'El monto debe ser mayor a 0.',
            'comprobante.max'    => 'El comprobante no puede pesar más de 5 MB.',
            'comprobante.mimes'  => 'El comprobante debe ser PDF, JPG o PNG.',
        ];
    }
}
