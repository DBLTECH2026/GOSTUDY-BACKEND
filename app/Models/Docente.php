<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Docente — extiende a Usuario (con rol='docente') con datos académicos.
 * (Modelo creado por Persona C porque A no lo había implementado todavía;
 *  cuando A termine, debe consolidarse — solo el dueño modifica.)
 */
class Docente extends Model
{
    protected $table = 'docentes';

    protected $fillable = [
        'usuario_id',
        'codigo_docente',
        'especialidad',
        'grado_academico',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
