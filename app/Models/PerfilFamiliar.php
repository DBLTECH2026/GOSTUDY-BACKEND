<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilFamiliar extends Model
{
    protected $table = 'perfiles_familiares';

    protected $fillable = [
        'estudiante_id',
        'tipo',
        'nombres',
        'apellidos',
        'dni',
        'telefono',
        'email',
        'ocupacion',
        'parentesco',
        'vive_con',
        'es_titular',
    ];

    protected $casts = [
        'vive_con'   => 'boolean',
        'es_titular' => 'boolean',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
