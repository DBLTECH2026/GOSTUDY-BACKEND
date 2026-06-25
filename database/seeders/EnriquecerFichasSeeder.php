<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Enriquecer fichas de matrícula con datos demo realistas.
 *
 * IDEMPOTENTE Y NO DESTRUCTIVO:
 *  - Solo rellena columnas de ubigeo/IE que estén en NULL (no pisa datos reales).
 *  - Crea 1 apoderado (perfiles_familiares) por estudiante SOLO si no tiene ninguno.
 *
 * Es seguro correrlo varias veces y en producción: a los alumnos que ya tengan
 * estos datos (p. ej. inscritos por el flujo real) no les toca nada.
 *
 * Datos derivados del DNI → siempre el mismo resultado (reproducible).
 */
class EnriquecerFichasSeeder extends Seeder
{
    /** Ubigeos demo (departamento, provincia, distrito). */
    private const UBIGEOS = [
        ['Lima', 'Lima', 'San Juan de Lurigancho'],
        ['Lima', 'Lima', 'Comas'],
        ['Lima', 'Lima', 'Villa El Salvador'],
        ['Lima', 'Lima', 'Los Olivos'],
        ['Lima', 'Lima', 'Ate'],
        ['Lima', 'Lima', 'San Martín de Porres'],
        ['Arequipa', 'Arequipa', 'Cerro Colorado'],
        ['La Libertad', 'Trujillo', 'El Porvenir'],
        ['Cusco', 'Cusco', 'Wanchaq'],
        ['Piura', 'Piura', 'Castilla'],
    ];

    private const IES = [
        'I.E. 1001 Mariscal Cáceres',
        'I.E. 2002 José Olaya',
        'I.E. Túpac Amaru',
        'I.E. San Martín de Porres',
        'I.E. Santa Rosa',
        'Colegio Particular Los Andes',
    ];

    private const NOMBRES_M = ['Carlos', 'José', 'Luis', 'Miguel', 'Juan', 'Pedro', 'Jorge', 'Víctor'];
    private const NOMBRES_F = ['María', 'Rosa', 'Carmen', 'Ana', 'Luisa', 'Elena', 'Patricia', 'Julia'];
    private const OCUPACIONES = ['Comerciante', 'Docente', 'Independiente', 'Empleado', 'Técnico', 'Ama de casa'];

    public function run(): void
    {
        $estudiantes = DB::table('estudiantes')->whereNull('deleted_at')->get();
        $rellenados = 0;
        $apoderados = 0;

        foreach ($estudiantes as $e) {
            // Semilla determinista a partir del DNI numérico.
            $semilla = (int) preg_replace('/\D/', '', (string) $e->dni);

            // 1) Ubigeo + IE — solo si están en NULL.
            [$dep, $prov, $dist] = self::UBIGEOS[$semilla % count(self::UBIGEOS)];
            $update = [];
            if ($e->departamento === null)   $update['departamento']   = $dep;
            if ($e->provincia === null)      $update['provincia']      = $prov;
            if ($e->distrito === null)       $update['distrito']       = $dist;
            if ($e->ie_procedencia === null) $update['ie_procedencia'] = self::IES[$semilla % count(self::IES)];
            if ($e->anio_procedencia === null) {
                $update['anio_procedencia'] = 2024 - ($semilla % 3); // 2022..2024
            }

            if ($update !== []) {
                DB::table('estudiantes')->where('id', $e->id)->update($update);
                $rellenados++;
            }

            // 2) Apoderado — solo si el estudiante no tiene ninguno.
            $tiene = DB::table('perfiles_familiares')->where('estudiante_id', $e->id)->exists();
            if (! $tiene) {
                $esMadre  = $semilla % 2 === 0;
                $nombres  = $esMadre
                    ? self::NOMBRES_F[$semilla % count(self::NOMBRES_F)]
                    : self::NOMBRES_M[$semilla % count(self::NOMBRES_M)];
                // Apellido paterno del alumno como apellido del apoderado.
                $apePaterno = trim(explode(' ', trim($e->apellidos))[0] ?? 'Pérez');

                DB::table('perfiles_familiares')->insert([
                    'estudiante_id' => $e->id,
                    'tipo'          => $esMadre ? 'madre' : 'padre',
                    'nombres'       => $nombres,
                    'apellidos'     => $apePaterno,
                    'dni'           => str_pad((string) (70000000 + ($semilla % 9999999)), 8, '0', STR_PAD_LEFT),
                    'telefono'      => '9' . str_pad((string) ($semilla % 100000000), 8, '0', STR_PAD_LEFT),
                    'email'         => null,
                    'ocupacion'     => self::OCUPACIONES[$semilla % count(self::OCUPACIONES)],
                    'parentesco'    => $esMadre ? 'Madre' : 'Padre',
                    'vive_con'      => true,
                    'es_titular'    => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $apoderados++;
            }
        }

        $this->command->info("Estudiantes enriquecidos: {$rellenados}");
        $this->command->info("Apoderados creados: {$apoderados}");
    }
}
