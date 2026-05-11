<?php

namespace Database\Seeders;

use App\Models\Grado;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use Illuminate\Database\Seeder;

/**
 * Crea 1 sección "A" por cada grado del periodo activo.
 * Lo agregó Persona C porque sin secciones no se puede aprobar
 * matrículas. Cuando Persona B termine su CatalogosSeeder real,
 * este queda obsoleto.
 */
class SeccionesSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = PeriodoAcademico::where('estado', 'activo')->first();
        if (! $periodo) {
            $this->command?->warn('SeccionesSeeder: no hay periodo activo. Corre CatalogosSeeder primero.');
            return;
        }

        $creadas = 0;
        foreach (Grado::all() as $grado) {
            $sec = Seccion::updateOrCreate(
                [
                    'grado_id'   => $grado->id,
                    'periodo_id' => $periodo->id,
                    'nombre'     => 'A',
                ],
                [
                    'capacidad' => 30,
                ],
            );
            if ($sec->wasRecentlyCreated) $creadas++;
        }

        $this->command?->info("SeccionesSeeder: {$creadas} secciones nuevas (1 'A' por grado del periodo {$periodo->descripcion}).");
    }
}
