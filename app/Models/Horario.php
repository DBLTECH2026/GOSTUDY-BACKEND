<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'seccion_curso_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'aula',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana'  => 'integer',
            'hora_inicio' => 'datetime:H:i',
            'hora_fin'    => 'datetime:H:i',
        ];
    }

    public function seccionCurso(): BelongsTo
    {
        // No hay modelo SeccionCurso (es pivot puro). Devolvemos genérico.
        return $this->belongsTo(\Illuminate\Database\Eloquent\Model::class, 'seccion_curso_id');
    }

    public static function diaNombre(int $dia): string
    {
        return match ($dia) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'Día ' . $dia,
        };
    }
}
