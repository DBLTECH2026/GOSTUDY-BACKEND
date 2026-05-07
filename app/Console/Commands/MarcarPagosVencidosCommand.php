<?php

namespace App\Console\Commands;

use App\Models\Pago;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * GOSTUDY — Persona C · Pagos.
 *
 * Marca como "vencido" todo pago con estado=pendiente cuya fecha_vencimiento
 * sea anterior a hoy. Pensado para correr en cron diario:
 *
 *   php artisan pagos:marcar-vencidos
 *
 * Programable en bootstrap/app.php → ->withSchedule(fn ($s) => $s->command(...))
 */
class MarcarPagosVencidosCommand extends Command
{
    protected $signature = 'pagos:marcar-vencidos
        {--dry-run : Solo muestra cuántos se marcarían, sin escribir.}';

    protected $description = 'Marca pagos pendientes con vencimiento pasado como vencidos.';

    public function handle(): int
    {
        $hoy = Carbon::today();

        $query = Pago::pendientes()->where('fecha_vencimiento', '<', $hoy);
        $total = $query->count();

        if ($total === 0) {
            $this->info('Nada que actualizar — no hay pagos pendientes con vencimiento anterior a ' . $hoy->toDateString() . '.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("DRY-RUN: se marcarían {$total} pagos como vencidos.");
            return self::SUCCESS;
        }

        $afectados = $query->update(['estado' => Pago::ESTADO_VENCIDO]);

        $this->info("Marcados {$afectados} pagos como vencidos.");
        return self::SUCCESS;
    }
}
