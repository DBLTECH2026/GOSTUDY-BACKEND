<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
            'dni'       => ['nullable', 'string', 'max:15', 'unique:usuarios,dni'],
            'telefono'  => ['nullable', 'string', 'max:20'],
            'rol'       => ['nullable', Rule::in(['admin', 'docente'])],
        ];
    }
}
