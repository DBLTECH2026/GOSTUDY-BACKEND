<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bimestre extends Model
{
    protected $table = 'bimestres';

    protected $fillable = [
        'periodo_id',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin'    => 'date',
            'orden'        => 'integer',
        ];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    public function semanas(): HasMany
    {
        return $this->hasMany(Semana::class)->orderBy('numero');
    }

    /** Determina si "hoy" cae dentro del rango del bimestre. */
    public function esActual(): bool
    {
        $hoy = now()->toDateString();
        return $this->fecha_inicio->toDateString() <= $hoy
            && $this->fecha_fin->toDateString() >= $hoy;
    }
}
