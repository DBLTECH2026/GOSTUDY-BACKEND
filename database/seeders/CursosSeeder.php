<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Grado;
use App\Models\Nivel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crea los cursos típicos del MINEDU peruano por nivel.
 * Idempotente (updateOrCreate por código).
 */
class CursosSeeder extends Seeder
{
    public function run(): void
    {
        // Plantilla de cursos por nivel. Cada nivel define una lista de
        // [nombre, horas/semana]. El curso se replica para TODOS los grados
        // del nivel (1ro a 6to primaria, 1ro a 5to secundaria, etc.).
        $plantilla = [
            'Inicial' => [
                ['Comunicación Integral',           5],
                ['Matemática Inicial',              4],
                ['Personal Social',                 3],
                ['Ciencia y Ambiente',              2],
                ['Psicomotricidad',                 2],
                ['Arte y Creatividad',              2],
                ['Religión',                        1],
            ],
            'Primaria' => [
                ['Comunicación',                    6],
                ['Matemática',                      6],
                ['Personal Social',                 4],
                ['Ciencia y Tecnología',            4],
                ['Educación Física',                3],
                ['Arte y Cultura',                  2],
                ['Inglés',                          3],
                ['Religión',                        2],
                ['Tutoría',                         1],
            ],
            'Secundaria' => [
                ['Comunicación',                    5],
                ['Matemática',                      6],
                ['Ciencia y Tecnología',            5],
                ['Ciencias Sociales',               4],
                ['Desarrollo Personal, Ciudadanía y Cívica', 3],
                ['Educación Física',                3],
                ['Arte y Cultura',                  2],
                ['Inglés',                          4],
                ['Educación Religiosa',             2],
                ['Educación para el Trabajo',       3],
                ['Tutoría',                         1],
            ],
        ];

        $creados = 0;
        $actualizados = 0;

        foreach ($plantilla as $nombreNivel => $cursos) {
            $nivel = Nivel::where('nombre', $nombreNivel)->first();
            if (! $nivel) continue;

            $grados = Grado::where('nivel_id', $nivel->id)->orderBy('orden')->get();

            foreach ($grados as $grado) {
                foreach ($cursos as [$nombre, $horas]) {
                    $codigo = $this->generarCodigo($nombreNivel, $grado->nombre, $nombre);

                    $curso = Curso::updateOrCreate(
                        ['codigo' => $codigo],
                        [
                            'grado_id'     => $grado->id,
                            'nombre'       => $nombre,
                            'horas_semana' => $horas,
                            'descripcion'  => null,
                        ],
                    );

                    if ($curso->wasRecentlyCreated) $creados++;
                    else $actualizados++;
                }
            }
        }

        $this->command?->info(
            "CursosSeeder: {$creados} cursos creados, {$actualizados} actualizados."
        );
    }

    private function generarCodigo(string $nivel, string $grado, string $curso): string
    {
        $prefNivel  = strtoupper(substr($nivel, 0, 3));         // INI, PRI, SEC
        $cleanGrado = Str::slug(str_replace(' ', '-', $grado)); // 1ro, 3-anos, etc.

        // Toma las iniciales de cada palabra (sin tildes/símbolos), p.ej.
        //   "Educación Física"             -> EF
        //   "Educación para el Trabajo"    -> EPET
        //   "Desarrollo Personal..."       -> DPCC
        //   "Comunicación"                 -> C (luego se completa con 3 chars)
        // Y agrega 3 chars del nombre limpio para diferenciar 1-palabra:
        $clean = preg_replace('/[^A-Za-z ]/', '', Str::ascii($curso));
        $iniciales = strtoupper(implode('', array_map(
            fn ($w) => substr($w, 0, 1),
            preg_split('/\s+/', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        )));
        $sufijo = strtoupper(substr(str_replace(' ', '', $clean), 0, 3));

        return "{$prefNivel}-{$cleanGrado}-{$iniciales}{$sufijo}";
    }
}
