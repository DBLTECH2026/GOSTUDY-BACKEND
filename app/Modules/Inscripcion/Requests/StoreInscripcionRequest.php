<?php

namespace App\Modules\Inscripcion\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Estudiante
            'dni_estudiante'       => ['required', 'string', 'size:8'],
            'nombres_estudiante'   => ['required', 'string', 'max:100'],
            'apellidos_estudiante' => ['required', 'string', 'max:100'],
            'fecha_nacimiento'     => ['required', 'date'],
            'sexo'                 => ['required', Rule::in(['M', 'F'])],
            'direccion'            => ['required', 'string', 'max:200'],
            'departamento'         => ['nullable', 'string', 'max:60'],
            'provincia'            => ['nullable', 'string', 'max:60'],
            'distrito'             => ['nullable', 'string', 'max:60'],
            'ie_procedencia'       => ['nullable', 'string', 'max:150'],
            'anio_procedencia'     => ['nullable', 'integer', 'min:1990', 'max:2100'],

            // Académico
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],

            // PIN
            'pin' => ['required', 'string', 'size:6', 'confirmed'],

            // Apoderado
            'apoderado_nombres'   => ['required', 'string', 'max:100'],
            'apoderado_apellidos' => ['required', 'string', 'max:100'],
            'apoderado_dni'       => ['required', 'string', 'size:8'],
            'apoderado_telefono'  => ['nullable', 'string', 'max:20'],
            'apoderado_email'     => ['nullable', 'email', 'max:150'],
            'apoderado_tipo'      => ['nullable', Rule::in(['padre', 'madre', 'apoderado'])],

            // Documentos opcionales en backend; el frontend los puede pedir como
            // obligatorios. Validados por tipo y tamaño si vienen.
            'comprobante_pago'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'certificado_estudios' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
