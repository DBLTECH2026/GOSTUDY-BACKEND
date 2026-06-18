<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    public const NOTAS = ['AD', 'A', 'B', 'C'];

    protected $table = 'calificaciones';

    protected $fillable = [
        'matricula_id', 'seccion_curso_id', 'competencia_id',
        'bimestre_id', 'nota', 'conclusion_descriptiva',
    ];
}
