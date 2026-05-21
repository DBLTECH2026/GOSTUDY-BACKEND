<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sección de un grado para un periodo académico.
 * (Modelo creado por Persona C porque B no lo había implementado todavía;
 *  cuando B termine, debe consolidarse — solo el dueño modifica.)
 */
class Seccion extends Model
{
    protected $table = 'secciones';

    protected $fillable = [
        'grado_id',
        'periodo_id',
        'docente_tutor_id',
        'nombre',
        'capacidad',
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
        ];
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function docenteTutor(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_tutor_id');
    }

    /**
     * Cursos dictados en esta sección (pivot seccion_curso con docente_id).
     */
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'seccion_curso')
            ->withPivot(['id', 'docente_id'])
            ->withTimestamps();
    }

    public function cupoDisponible(): int
    {
        return max(0, (int) $this->capacidad - $this->matriculas()->where('estado', 'activa')->count());
    }
}
