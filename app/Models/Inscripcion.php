<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';

    protected $fillable = [
        'codigo_inscripcion',
        'periodo_id',
        'nivel_id',
        'grado_id',
        'seccion_sugerida_id',
        'dni_estudiante',
        'nombres_estudiante',
        'apellidos_estudiante',
        'fecha_nacimiento',
        'sexo',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'ie_procedencia',
        'anio_procedencia',
        'pin_hash',
        'datos_familiares',
        'estado',
        'motivo_rechazo',
        'comprobante_url',
        'comprobante_pago_url',
        'certificado_estudios_url',
        'ip_origen',
        'fecha_inscripcion',
        'aprobada_por',
        'aprobada_en',
    ];

    protected $casts = [
        'fecha_nacimiento'  => 'date',
        'fecha_inscripcion' => 'datetime',
        'aprobada_en'       => 'datetime',
        'datos_familiares'  => 'array',
    ];

    protected $hidden = ['pin_hash'];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobada_por');
    }
}
