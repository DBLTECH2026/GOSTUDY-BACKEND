<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContenidoSemana extends Model
{
    protected $table = 'contenido_semana';

    protected $fillable = [
        'semana_id',
        'seccion_curso_id',
        'titulo',
        'descripcion',
        'recursos_url',
        'tarea',
    ];

    public function semana(): BelongsTo
    {
        return $this->belongsTo(Semana::class);
    }
}
