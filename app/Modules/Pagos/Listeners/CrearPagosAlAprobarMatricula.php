<?php

namespace App\Modules\Pagos\Listeners;

use App\Modules\Matricula\Events\MatriculaAprobada;
use App\Modules\Pagos\Services\CrearPagosService;

/**
 * GOSTUDY — Persona C · Pagos.
 *
 * Escucha el evento MatriculaAprobada (lo dispara Persona B al aprobar una
 * inscripción / matrícula) y dispara la generación del plan de cobranza
 * (1 matrícula + 10 pensiones).
 *
 * El registro del listener queda pendiente hasta que Persona B haya creado
 * la clase del evento; ver EventServiceProvider en cuanto el evento exista.
 */
class CrearPagosAlAprobarMatricula
{
    public function __construct(
        private readonly CrearPagosService $crearPagos,
    ) {}

    public function handle(MatriculaAprobada $event): void
    {
        $this->crearPagos->crearPagosParaMatricula($event->matricula);
    }
}
