<?php

namespace Database\Seeders;

use App\Models\Bimestre;
use App\Models\PeriodoAcademico;
use App\Models\Semana;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Crea 4 bimestres con sus semanas (8 c/u) para el periodo activo.
 * Calendario realista del año escolar peruano.
 */
class CalendarioAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = PeriodoAcademico::where('estado', 'activo')->first();
        if (! $periodo) {
            $this->command?->warn('CalendarioAcademicoSeeder: no hay periodo activo.');
            return;
        }

        $anio = (int) $periodo->anio;

        // Plantilla de bimestres del año académico peruano
        $bimestres = [
            ['orden' => 1, 'nombre' => 'Bimestre I',   'inicio' => "{$anio}-03-03", 'fin' => "{$anio}-05-09"],
            ['orden' => 2, 'nombre' => 'Bimestre II',  'inicio' => "{$anio}-05-12", 'fin' => "{$anio}-07-18"],
            ['orden' => 3, 'nombre' => 'Bimestre III', 'inicio' => "{$anio}-08-04", 'fin' => "{$anio}-10-10"],
            ['orden' => 4, 'nombre' => 'Bimestre IV',  'inicio' => "{$anio}-10-13", 'fin' => "{$anio}-12-19"],
        ];

        $countBim = 0;
        $countSem = 0;

        foreach ($bimestres as $b) {
            $bimestre = Bimestre::updateOrCreate(
                ['periodo_id' => $periodo->id, 'orden' => $b['orden']],
                [
                    'nombre'       => $b['nombre'],
                    'fecha_inicio' => $b['inicio'],
                    'fecha_fin'    => $b['fin'],
                ],
            );
            if ($bimestre->wasRecentlyCreated) $countBim++;

            // Generar semanas dentro del bimestre (lunes a viernes)
            $cursor = Carbon::parse($b['inicio'])->startOfWeek(Carbon::MONDAY);
            $hastaSiq = Carbon::parse($b['fin']);
            $numero = 1;

            while ($cursor->lte($hastaSiq)) {
                $finSemana = $cursor->copy()->endOfWeek(Carbon::FRIDAY);
                if ($finSemana->gt($hastaSiq)) $finSemana = $hastaSiq->copy();

                $semana = Semana::updateOrCreate(
                    ['bimestre_id' => $bimestre->id, 'numero' => $numero],
                    [
                        'fecha_inicio' => $cursor->toDateString(),
                        'fecha_fin'    => $finSemana->toDateString(),
                    ],
                );
                if ($semana->wasRecentlyCreated) $countSem++;

                $cursor->addWeek();
                $numero++;
            }
        }

        $this->command?->info(
            "CalendarioAcademicoSeeder: {$countBim} bimestres y {$countSem} semanas creadas."
        );
    }
}
