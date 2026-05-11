<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortalRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dni'              => ['required', 'string', 'size:8', 'unique:estudiantes,dni'],
            'pin'              => ['required', 'string', 'size:6', 'confirmed'],
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
        ];
    }
}
