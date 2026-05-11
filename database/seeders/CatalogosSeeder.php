<?php

namespace Database\Seeders;

use App\Models\Grado;
use App\Models\Nivel;
use App\Models\PeriodoAcademico;
use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        // ── Niveles ─────────────────────────────────────────
        $niveles = [
            ['nombre' => 'Inicial',    'orden' => 1],
            ['nombre' => 'Primaria',   'orden' => 2],
            ['nombre' => 'Secundaria', 'orden' => 3],
        ];
        foreach ($niveles as $n) {
            Nivel::updateOrCreate(['nombre' => $n['nombre']], $n);
        }

        // ── Grados ──────────────────────────────────────────
        $inicial    = Nivel::where('nombre', 'Inicial')->first();
        $primaria   = Nivel::where('nombre', 'Primaria')->first();
        $secundaria = Nivel::where('nombre', 'Secundaria')->first();

        $gradosInicial    = ['3 años', '4 años', '5 años'];
        $gradosPrimaria   = ['1ro', '2do', '3ro', '4to', '5to', '6to'];
        $gradosSecundaria = ['1ro', '2do', '3ro', '4to', '5to'];

        foreach ($gradosInicial as $i => $g) {
            Grado::updateOrCreate(
                ['nivel_id' => $inicial->id, 'nombre' => $g],
                ['orden' => $i + 1],
            );
        }
        foreach ($gradosPrimaria as $i => $g) {
            Grado::updateOrCreate(
                ['nivel_id' => $primaria->id, 'nombre' => $g],
                ['orden' => $i + 1],
            );
        }
        foreach ($gradosSecundaria as $i => $g) {
            Grado::updateOrCreate(
                ['nivel_id' => $secundaria->id, 'nombre' => $g],
                ['orden' => $i + 1],
            );
        }

        // ── Periodo académico activo ────────────────────────
        PeriodoAcademico::updateOrCreate(
            ['anio' => 2026, 'descripcion' => '2026 — I'],
            [
                'fecha_inicio' => '2026-03-01',
                'fecha_fin'    => '2026-07-31',
                'estado'       => 'activo',
            ]
        );
    }
}
