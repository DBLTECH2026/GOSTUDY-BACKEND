<?php

namespace App\Modules\Matricula\Events;

use App\Models\Matricula;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se aprueba una matrícula.
 * Lo escucha el módulo de Pagos (Persona C) para generar el plan de
 * cobranza (1 matrícula + 10 pensiones).
 *
 * (Stub creado por Persona C para desbloquear el flujo end-to-end.
 *  Cuando Persona B implemente su módulo de Matrícula formal,
 *  puede mantener esta clase o consolidarla — su namespace es M.)
 */
class MatriculaAprobada
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Matricula $matricula) {}
}
