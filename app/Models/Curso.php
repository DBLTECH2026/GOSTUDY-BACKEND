<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use SoftDeletes;

    protected $table = 'cursos';

    protected $fillable = [
        'grado_id',
        'nombre',
        'codigo',
        'horas_semana',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'horas_semana' => 'integer',
        ];
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    /**
     * Antes de hacer soft delete, renombra el codigo agregando un sufijo
     * para liberar el slug "activo" — de lo contrario el índice UNIQUE de
     * MySQL impide reutilizar el mismo código (la fila soft-deleted sigue
     * ocupando el slot).
     */
    public function delete()
    {
        if (! $this->trashed() && $this->codigo && ! str_contains($this->codigo, '__del_')) {
            // Sufijo corto basado en el id (siempre único por fila).
            $this->codigo = $this->codigo . '__del_' . $this->id;
            $this->saveQuietly();
        }
        return parent::delete();
    }

    /**
     * Secciones donde se dicta este curso (pivot seccion_curso).
     */
    public function secciones(): BelongsToMany
    {
        return $this->belongsToMany(Seccion::class, 'seccion_curso')
            ->withPivot(['id', 'docente_id'])
            ->withTimestamps();
    }
}
