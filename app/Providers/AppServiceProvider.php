<?php

namespace App\Providers;

use App\Modules\Matricula\Events\MatriculaAprobada;
use App\Modules\Pagos\Listeners\CrearPagosAlAprobarMatricula;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Persona C — registro manual del listener de Pagos porque los
        // módulos viven fuera de app/Listeners (la convención por defecto).
        Event::listen(
            MatriculaAprobada::class,
            CrearPagosAlAprobarMatricula::class,
        );
    }
}
