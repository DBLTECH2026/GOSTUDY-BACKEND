<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semana extends Model
{
    protected $table = 'semanas';

    protected $fillable = [
        'bimestre_id',
        'numero',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin'    => 'date',
            'numero'       => 'integer',
        ];
    }

    public function bimestre(): BelongsTo
    {
        return $this->belongsTo(Bimestre::class);
    }

    public function contenidos(): HasMany
    {
        return $this->hasMany(ContenidoSemana::class);
    }

    public function esActual(): bool
    {
        $hoy = now()->toDateString();
        return $this->fecha_inicio->toDateString() <= $hoy
            && $this->fecha_fin->toDateString() >= $hoy;
    }
}
