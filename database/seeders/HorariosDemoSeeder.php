<?php

namespace Database\Seeders;

use App\Models\Horario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Genera horarios demo distribuyendo cada asignación seccion_curso
 * en franjas de 1.5h entre lunes y viernes. La cantidad de slots
 * depende de horas_semana del curso (2h → 1 slot, 4h → 2 slots, 6h → 3 slots).
 *
 * Solo crea horarios para asignaciones que aún no tengan ninguno.
 */
class HorariosDemoSeeder extends Seeder
{
    /** Slots horarios disponibles (inicio, fin) — 1.5h cada uno. */
    private array $slots = [
        ['07:30', '09:00'],
        ['09:00', '10:30'],
        ['11:00', '12:30'],
        ['12:30', '14:00'],
    ];

    public function run(): void
    {
        // Tomamos todas las asignaciones que no tienen horarios todavía.
        $asignaciones = DB::table('seccion_curso as sc')
            ->leftJoin('horarios as h', 'h.seccion_curso_id', '=', 'sc.id')
            ->join('cursos as c', 'c.id', '=', 'sc.curso_id')
            ->whereNull('h.id')
            ->select('sc.id', 'sc.seccion_id', 'c.horas_semana')
            ->get();

        if ($asignaciones->isEmpty()) {
            $this->command?->info(
                'HorariosDemoSeeder: no hay asignaciones seccion_curso sin horario. '
                . 'Crea asignaciones primero en /asignaciones.'
            );
            return;
        }

        // Lleva el conteo de slots usados por sección para no chocar dos cursos a la misma hora.
        $usadosPorSeccion = []; // [seccion_id => [ "1-0", "1-1", ... ]]

        $creados = 0;
        foreach ($asignaciones as $a) {
            $horas = max(1, (int) $a->horas_semana);
            $numSlots = max(1, min(5, (int) ceil($horas / 2))); // 2h c/u aprox

            $diasUsados = [];
            $slotsBase = $usadosPorSeccion[$a->seccion_id] ?? [];

            // Recorre la grilla 5 días × 4 slots y va asignando los primeros libres
            for ($dia = 1; $dia <= 5 && count($diasUsados) < $numSlots; $dia++) {
                for ($slotIdx = 0; $slotIdx < count($this->slots); $slotIdx++) {
                    $key = "{$dia}-{$slotIdx}";
                    if (in_array($key, $slotsBase, true)) continue;
                    if (in_array($dia, $diasUsados, true)) continue; // un curso máx 1 vez al día

                    [$ini, $fin] = $this->slots[$slotIdx];
                    Horario::create([
                        'seccion_curso_id' => $a->id,
                        'dia_semana'       => $dia,
                        'hora_inicio'      => $ini,
                        'hora_fin'         => $fin,
                        'aula'             => 'Aula ' . (100 + ($a->seccion_id % 30)),
                    ]);
                    $slotsBase[] = $key;
                    $diasUsados[] = $dia;
                    $creados++;
                    break; // siguiente día
                }
            }

            $usadosPorSeccion[$a->seccion_id] = $slotsBase;
        }

        $this->command?->info("HorariosDemoSeeder: {$creados} horarios creados.");
    }
}
