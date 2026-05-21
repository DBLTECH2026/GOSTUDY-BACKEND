<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialSemana extends Model
{
    protected $table = 'materiales_semana';

    protected $fillable = [
        'semana_id',
        'seccion_curso_id',
        'nombre_original',
        'ruta',
        'tipo',
        'tamano',
        'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
        ];
    }

    public function semana(): BelongsTo
    {
        return $this->belongsTo(Semana::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }
}
