<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Matrícula activa de un estudiante en un periodo y sección.
 * (Modelo creado por Persona C porque B no lo había implementado todavía;
 *  cuando B termine, debe consolidarse — solo el dueño modifica.)
 */
class Matricula extends Model
{
    use SoftDeletes;

    public const ESTADO_ACTIVA   = 'activa';
    public const ESTADO_RETIRADA = 'retirada';
    public const ESTADO_EGRESADA = 'egresada';

    protected $table = 'matriculas';

    protected $fillable = [
        'estudiante_id',
        'periodo_id',
        'seccion_id',
        'inscripcion_id',
        'fecha_matricula',
        'estado',
        'observaciones',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
