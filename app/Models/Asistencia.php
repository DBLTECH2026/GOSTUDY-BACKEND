<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    public const ESTADOS = ['presente', 'tarde', 'falta', 'justificada'];

    protected $table = 'asistencias';

    protected $fillable = ['matricula_id', 'seccion_curso_id', 'fecha', 'estado'];

    protected $casts = ['fecha' => 'date'];
}
