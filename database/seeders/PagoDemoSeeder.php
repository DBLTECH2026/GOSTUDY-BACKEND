<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Modules\Pagos\Services\CrearPagosService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * GOSTUDY — Persona C · Pagos.
 *
 * Seeder demo del módulo Pagos. Recorre todas las matrículas activas y
 * genera el plan de cobranza (1 matrícula + 10 pensiones por matrícula).
 * Para que la UI luzca realista, marca aleatoriamente como "pagado"
 * los pagos cuyo vencimiento ya pasó (~70%) y deja unos pocos vencidos.
 *
 * Se corre con:
 *   php artisan db:seed --class=PagoDemoSeeder
 *
 * Requiere que los seeders de Persona A (estudiantes) y Persona B
 * (matrículas/secciones/periodos) hayan corrido antes.
 */
class PagoDemoSeeder extends Seeder
{
    public function run(CrearPagosService $crearPagos): void
    {
        $matriculas = DB::table('matriculas')
            ->where('estado', 'activa')
            ->whereNull('deleted_at')
            ->get(['id', 'periodo_id', 'fecha_matricula']);

        if ($matriculas->isEmpty()) {
            $this->command?->warn('PagoDemoSeeder: no hay matrículas activas. Corre los seeders de A y B primero.');
            return;
        }

        $periodoAnio = DB::table('periodos_academicos')
            ->whereIn('id', $matriculas->pluck('periodo_id')->unique())
            ->pluck('anio', 'id');

        $hoy = Carbon::today();
        $totalCreados   = 0;
        $totalPagados   = 0;
        $totalVencidos  = 0;
        $metodos = [Pago::METODO_EFECTIVO, Pago::METODO_TRANSFERENCIA, Pago::METODO_YAPE, Pago::METODO_PLIN];

        foreach ($matriculas as $m) {
            $anio  = (int) ($periodoAnio[$m->periodo_id] ?? Carbon::parse($m->fecha_matricula)->format('Y'));
            $pagos = $crearPagos->crearPagosParaMatricula(
                (int) $m->id,
                $m->fecha_matricula,
                $anio,
            );

            $totalCreados += $pagos->count();

            foreach ($pagos as $pago) {
                if ($pago->fecha_vencimiento->isAfter($hoy)) {
                    continue;
                }

                $roll = random_int(1, 100);
                if ($roll <= 70) {
                    $pago->update([
                        'estado'      => Pago::ESTADO_PAGADO,
                        'fecha_pago'  => $pago->fecha_vencimiento->copy()->subDays(random_int(0, 3)),
                        'metodo'      => $metodos[array_rand($metodos)],
                    ]);
                    $totalPagados++;
                } elseif ($roll <= 90) {
                    $pago->update(['estado' => Pago::ESTADO_VENCIDO]);
                    $totalVencidos++;
                }
            }
        }

        $this->command?->info(sprintf(
            'PagoDemoSeeder: %d pagos creados sobre %d matrículas (%d pagados, %d vencidos).',
            $totalCreados,
            $matriculas->count(),
            $totalPagados,
            $totalVencidos,
        ));
    }
}
