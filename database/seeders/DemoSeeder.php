<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Bimestre;
use App\Models\Calificacion;
use App\Models\Competencia;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Semana;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Limpia la data transaccional (conservando catálogos, el admin Luis y el
 * estudiante Luis) y crea data de prueba completa: docentes, cursos con
 * competencias, asignaciones, horarios, bimestres/semanas, 19 estudiantes
 * con matrículas + pagos, y notas/asistencia de ejemplo.
 */
class DemoSeeder extends Seeder
{
    private const ADMIN_EMAIL  = 'u23212682@utp.edu.pe';
    private const EST_DNI_KEEP = '72141622';
    private const PIN_DEMO     = '123456';
    private const PASS_DOCENTE = 'docente123';

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'asistencias', 'calificaciones', 'materiales_semana', 'contenido_semana',
            'horarios', 'semanas', 'bimestres', 'seccion_curso', 'competencias',
            'pagos', 'fichas_matricula', 'matriculas', 'inscripciones',
            'perfiles_familiares',
        ] as $t) {
            DB::table($t)->truncate();
        }
        DB::table('cursos')->delete();
        DB::table('estudiantes')->where('dni', '!=', self::EST_DNI_KEEP)->delete();
        DB::table('docentes')->delete();
        DB::table('usuarios')->where('email', '!=', self::ADMIN_EMAIL)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $periodoId = DB::table('periodos_academicos')->where('estado', 'activo')->value('id')
            ?? DB::table('periodos_academicos')->value('id');

        $this->seedBimestresSemanas($periodoId);
        $docentes = $this->seedDocentes();
        $this->seedCursosCompetenciasAsignacionesHorarios($docentes, $periodoId);
        $this->seedEstudiantesMatriculas($periodoId);
        $this->seedNotasAsistenciaEjemplo($periodoId);

        $this->command->info('DemoSeeder completado.');
    }

    private function seedBimestresSemanas(int $periodoId): void
    {
        $base = [
            ['Bimestre I', '2026-03-01', '2026-05-09', 1],
            ['Bimestre II', '2026-05-12', '2026-07-18', 2],
            ['Bimestre III', '2026-08-04', '2026-10-10', 3],
            ['Bimestre IV', '2026-10-13', '2026-12-19', 4],
        ];
        foreach ($base as [$nombre, $ini, $fin, $orden]) {
            $b = Bimestre::create([
                'periodo_id' => $periodoId, 'nombre' => $nombre,
                'fecha_inicio' => $ini, 'fecha_fin' => $fin, 'orden' => $orden,
            ]);
            $cursor = Carbon::parse($ini);
            for ($n = 1; $n <= 8; $n++) {
                $semIni = $cursor->copy();
                $semFin = $cursor->copy()->addDays(4);
                Semana::create([
                    'bimestre_id' => $b->id, 'numero' => $n,
                    'fecha_inicio' => $semIni->toDateString(),
                    'fecha_fin'    => $semFin->toDateString(),
                ]);
                $cursor->addDays(7);
            }
        }
    }

    /** @return array<int,int> índice→docente_id */
    private function seedDocentes(): array
    {
        $defs = [
            ['Carlos', 'Ramírez', 'Matemática'],
            ['María', 'Flores', 'Comunicación'],
            ['Jorge', 'Quispe', 'Ciencia y Tecnología'],
            ['Ana', 'Torres', 'Personal Social'],
            ['Luis', 'Mendoza', 'Arte y Cultura'],
            ['Rosa', 'Díaz', 'Educación Física'],
        ];
        $ids = [];
        foreach ($defs as $i => [$nom, $ape, $esp]) {
            $u = Usuario::create([
                'nombres' => $nom, 'apellidos' => $ape,
                'email' => 'docente' . ($i + 1) . '@gostudy.test',
                'password' => Hash::make(self::PASS_DOCENTE),
                'rol' => 'docente', 'estado' => 'activo',
            ]);
            $d = Docente::create([
                'usuario_id' => $u->id,
                'codigo_docente' => 'DOC-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'especialidad' => $esp,
                'grado_academico' => 'Licenciado',
            ]);
            $ids[$i] = $d->id;
        }
        return $ids;
    }

    private function seedCursosCompetenciasAsignacionesHorarios(array $docentes, int $periodoId): void
    {
        $areas = [
            ['Matemática', 5, ['Resuelve problemas de cantidad', 'Resuelve problemas de regularidad'], 0],
            ['Comunicación', 5, ['Se comunica oralmente', 'Lee diversos textos', 'Escribe diversos textos'], 1],
            ['Ciencia y Tecnología', 4, ['Indaga mediante métodos científicos', 'Explica el mundo físico'], 2],
            ['Personal Social', 4, ['Construye su identidad', 'Convive y participa democráticamente'], 3],
            ['Arte y Cultura', 3, ['Aprecia manifestaciones artístico-culturales'], 4],
            ['Educación Física', 3, ['Se desenvuelve de manera autónoma'], 5],
        ];

        // Solo secciones del periodo activo
        $secciones = DB::table('secciones')->where('periodo_id', $periodoId)->get(['id', 'grado_id']);

        foreach ($secciones as $sec) {
            foreach ($areas as [$nombre, $horas, $comps, $docIdx]) {
                $curso = Curso::create([
                    'grado_id' => $sec->grado_id,
                    'nombre' => $nombre,
                    'codigo' => strtoupper(Str::slug($nombre, '')) . '-' . $sec->grado_id . '-' . Str::upper(Str::random(3)),
                    'horas_semana' => $horas,
                    'descripcion' => "Curso de {$nombre}",
                ]);
                foreach ($comps as $orden => $cnom) {
                    Competencia::create([
                        'curso_id' => $curso->id, 'nombre' => $cnom,
                        'descripcion' => null, 'orden' => $orden + 1,
                    ]);
                }
                $scId = DB::table('seccion_curso')->insertGetId([
                    'seccion_id' => $sec->id,
                    'curso_id' => $curso->id,
                    'docente_id' => $docentes[$docIdx] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('horarios')->insert([
                    'seccion_curso_id' => $scId,
                    'dia_semana' => ($docIdx % 5) + 1,
                    'hora_inicio' => sprintf('%02d:00:00', 8 + $docIdx),
                    'hora_fin' => sprintf('%02d:00:00', 9 + $docIdx),
                    'aula' => 'Aula ' . $sec->grado_id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('secciones')->where('id', $sec->id)
                ->update(['docente_tutor_id' => $docentes[0] ?? null]);
        }
    }

    private function seedEstudiantesMatriculas(int $periodoId): void
    {
        $nombres = [
            ['Pedro', 'Salazar'], ['Lucía', 'Vega'], ['Diego', 'Rojas'], ['Camila', 'Núñez'],
            ['Mateo', 'Castro'], ['Valentina', 'Ríos'], ['Sebastián', 'Paredes'], ['Fernanda', 'León'],
            ['Joaquín', 'Mora'], ['Daniela', 'Cruz'], ['Bruno', 'Soto'], ['Antonia', 'Herrera'],
            ['Gabriel', 'Campos'], ['Renata', 'Ortiz'], ['Tomás', 'Vargas'], ['Isabela', 'Reyes'],
            ['Emilio', 'Guzmán'], ['Mía', 'Fuentes'], ['Adrián', 'Cordova'],
        ];
        // grado_id reales: 1-3 inicial, 4-9 primaria, 10-14 secundaria
        $gradosDestino = [1, 2, 3, 4, 4, 5, 6, 7, 8, 9, 10, 10, 11, 11, 12, 12, 13, 14, 14];

        foreach ($nombres as $i => [$nom, $ape]) {
            $gradoId = $gradosDestino[$i];
            $seccionId = DB::table('secciones')->where('grado_id', $gradoId)
                ->where('periodo_id', $periodoId)->value('id');
            if (! $seccionId) continue;

            $est = Estudiante::create([
                'codigo_estudiante' => 'EST-2026-' . Str::upper(Str::random(5)),
                'dni' => str_pad((string) (80000000 + $i), 8, '0', STR_PAD_LEFT),
                'pin' => Hash::make(self::PIN_DEMO),
                'nombres' => $nom, 'apellidos' => $ape,
                'fecha_nacimiento' => '2012-01-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                'sexo' => $i % 2 === 0 ? 'M' : 'F',
                'direccion' => 'Av. Demo ' . ($i + 1),
                'estado' => 'activo',
            ]);
            $this->matricular($est->id, $seccionId, $periodoId);
        }

        // Estudiante Luis conservado: matricularlo en 1ro de secundaria (grado 10)
        $luis = DB::table('estudiantes')->where('dni', self::EST_DNI_KEEP)->first();
        if ($luis) {
            $secSec = DB::table('secciones')->where('grado_id', 10)
                ->where('periodo_id', $periodoId)->value('id');
            if ($secSec && ! DB::table('matriculas')->where('estudiante_id', $luis->id)->exists()) {
                $this->matricular($luis->id, $secSec, $periodoId);
            }
        }
    }

    private function matricular(int $estudianteId, int $seccionId, int $periodoId): void
    {
        $mat = Matricula::create([
            'estudiante_id' => $estudianteId,
            'periodo_id' => $periodoId,
            'seccion_id' => $seccionId,
            'estado' => 'activa',
            'fecha_matricula' => now()->toDateString(),
        ]);
        DB::table('pagos')->insert([
            [
                'matricula_id' => $mat->id, 'concepto' => 'matricula',
                'descripcion' => 'Matrícula 2026', 'monto' => 150.00, 'mes' => null,
                'fecha_vencimiento' => now()->toDateString(),
                'fecha_pago' => now()->toDateString(), 'metodo' => 'efectivo',
                'estado' => 'pagado',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'matricula_id' => $mat->id, 'concepto' => 'pension',
                'descripcion' => 'Pensión Marzo', 'monto' => 200.00, 'mes' => 3,
                'fecha_vencimiento' => now()->addDays(15)->toDateString(),
                'fecha_pago' => null, 'metodo' => null,
                'estado' => 'pendiente',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    private function seedNotasAsistenciaEjemplo(int $periodoId): void
    {
        $bimestre1 = Bimestre::where('periodo_id', $periodoId)->orderBy('orden')->first();
        if (! $bimestre1) return;

        $seccionId = DB::table('secciones')->where('grado_id', 10)
            ->where('periodo_id', $periodoId)->value('id');
        if (! $seccionId) return;

        $scList = DB::table('seccion_curso')->where('seccion_id', $seccionId)->get();
        $matriculas = DB::table('matriculas')->where('seccion_id', $seccionId)->pluck('id')->all();
        $notas = ['AD', 'A', 'B', 'C'];
        $estadosAsist = ['presente', 'presente', 'tarde', 'falta'];

        foreach ($scList as $sc) {
            $comps = DB::table('competencias')->where('curso_id', $sc->curso_id)->pluck('id')->all();
            foreach ($matriculas as $idx => $matId) {
                foreach ($comps as $compId) {
                    Calificacion::updateOrCreate(
                        ['matricula_id' => $matId, 'competencia_id' => $compId, 'bimestre_id' => $bimestre1->id],
                        ['seccion_curso_id' => $sc->id, 'nota' => $notas[($idx + $compId) % 4]],
                    );
                }
                foreach (['2026-03-02', '2026-03-03', '2026-03-04'] as $fIdx => $fecha) {
                    Asistencia::updateOrCreate(
                        ['matricula_id' => $matId, 'seccion_curso_id' => $sc->id, 'fecha' => $fecha],
                        ['estado' => $estadosAsist[($idx + $fIdx) % 4]],
                    );
                }
            }
        }
    }
}
