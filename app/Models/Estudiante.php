<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Estudiante extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'estudiantes';

    protected $fillable = [
        'codigo_estudiante',
        'dni',
        'pin',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'ie_procedencia',
        'anio_procedencia',
        'foto_url',
        'estado',
    ];

    protected $hidden = [
        'pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'pin' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->pin;
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }
}