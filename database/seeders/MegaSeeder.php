<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * MegaSeeder — versión AMPLIADA de DemoSeeder.
 *
 * Limpia la data transaccional (conservando catálogos, el admin Luis y el
 * estudiante Luis) y crea un dataset masivo: secciones A/B/C, 15 docentes,
 * cursos con competencias, asignaciones, horarios completos sin solape,
 * bimestres/semanas, 200+ estudiantes con matrículas + pagos mixtos,
 * miles de notas (con ~25% desaprobados), asistencias y pagos a docentes.
 *
 * Optimizado con inserts batch/chunk para terminar en < 90s.
 */
class MegaSeeder extends Seeder
{
    private const ADMIN_EMAIL  = 'u23212682@utp.edu.pe';
    private const EST_DNI_KEEP = '72141622';
    private const PIN_DEMO     = '123456';
    private const PASS_DOCENTE = 'docente123';

    private const ALUMNOS_POR_SECCION = 7;
    private const CHUNK = 50;
    private const CHUNK_NOTAS = 200;

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'pagos_docentes',
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

        $this->seedSecciones($periodoId);
        $this->seedBimestresSemanas($periodoId);
        $docentes = $this->seedDocentes();
        $this->seedCursosCompetenciasAsignacionesHorarios($docentes, $periodoId);
        $this->seedEstudiantesMatriculasPagos($periodoId);
        $this->seedNotas($periodoId);
        $this->seedAsistencias();
        $this->seedPagosDocentes($docentes, $periodoId);

        $this->command->info('MegaSeeder completado.');
    }

    /** Asegura secciones A, B (y C para secundaria, grados 10-14) por grado. */
    private function seedSecciones(int $periodoId): void
    {
        $rows = [];
        foreach (range(1, 14) as $gradoId) {
            $nombres = $gradoId >= 10 ? ['A', 'B', 'C'] : ['A', 'B'];
            foreach ($nombres as $nombre) {
                $rows[] = [
                    'grado_id' => $gradoId,
                    'periodo_id' => $periodoId,
                    'nombre' => $nombre,
                    'capacidad' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        // unique [grado_id, periodo_id, nombre] respetado vía insertOrIgnore
        DB::table('secciones')->insertOrIgnore($rows);
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
            $bimestreId = DB::table('bimestres')->insertGetId([
                'periodo_id' => $periodoId, 'nombre' => $nombre,
                'fecha_inicio' => $ini, 'fecha_fin' => $fin, 'orden' => $orden,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $cursor = Carbon::parse($ini);
            $semanas = [];
            for ($n = 1; $n <= 8; $n++) {
                $semanas[] = [
                    'bimestre_id' => $bimestreId, 'numero' => $n,
                    'fecha_inicio' => $cursor->copy()->toDateString(),
                    'fecha_fin'    => $cursor->copy()->addDays(4)->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $cursor->addDays(7);
            }
            DB::table('semanas')->insert($semanas);
        }
    }

    /** @return array<int,int> índice→docente_id (15 docentes) */
    private function seedDocentes(): array
    {
        $defs = [
            ['Carlos', 'Ramírez', 'Matemática'],
            ['María', 'Flores', 'Comunicación'],
            ['Jorge', 'Quispe', 'Ciencia y Tecnología'],
            ['Ana', 'Torres', 'Personal Social'],
            ['Luis', 'Mendoza', 'Arte y Cultura'],
            ['Rosa', 'Díaz', 'Educación Física'],
            ['Pedro', 'Huamán', 'Matemática'],
            ['Carmen', 'Salazar', 'Comunicación'],
            ['Miguel', 'Rojas', 'Ciencia y Tecnología'],
            ['Sofía', 'Vargas', 'Personal Social'],
            ['Raúl', 'Castro', 'Arte y Cultura'],
            ['Patricia', 'Núñez', 'Educación Física'],
            ['Fernando', 'Paredes', 'Matemática'],
            ['Gloria', 'León', 'Comunicación'],
            ['Andrés', 'Mora', 'Ciencia y Tecnología'],
        ];
        $ids = [];
        foreach ($defs as $i => [$nom, $ape, $esp]) {
            $uId = DB::table('usuarios')->insertGetId([
                'nombres' => $nom, 'apellidos' => $ape,
                'email' => 'docente' . ($i + 1) . '@gostudy.test',
                'password' => Hash::make(self::PASS_DOCENTE),
                'rol' => 'docente', 'estado' => 'activo',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $dId = DB::table('docentes')->insertGetId([
                'usuario_id' => $uId,
                'codigo_docente' => 'DOC-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'especialidad' => $esp,
                'grado_academico' => 'Licenciado',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $ids[$i] = $dId;
        }
        return $ids;
    }

    private function seedCursosCompetenciasAsignacionesHorarios(array $docentes, int $periodoId): void
    {
        $areas = [
            ['Matemática', 5, ['Resuelve problemas de cantidad', 'Resuelve problemas de regularidad', 'Resuelve problemas de forma y movimiento']],
            ['Comunicación', 5, ['Se comunica oralmente', 'Lee diversos textos', 'Escribe diversos textos']],
            ['Ciencia y Tecnología', 4, ['Indaga mediante métodos científicos', 'Explica el mundo físico']],
            ['Personal Social', 4, ['Construye su identidad', 'Convive y participa democráticamente']],
            ['Arte y Cultura', 3, ['Aprecia manifestaciones artístico-culturales', 'Crea proyectos desde los lenguajes artísticos']],
            ['Educación Física', 3, ['Se desenvuelve de manera autónoma', 'Asume una vida saludable']],
        ];

        $secciones = DB::table('secciones')->where('periodo_id', $periodoId)->get(['id', 'grado_id']);
        $docCount = count($docentes);
        $docKeys = array_keys($docentes);
        $rotIdx = 0;

        foreach ($secciones as $sec) {
            $cursoIdx = 0;
            // Horario sin solape: 6 cursos repartidos en grilla Lun-Vie desde 08:00.
            // Cada (dia, hora) es único dentro de la sección.
            foreach ($areas as [$nombre, $horas, $comps]) {
                $cursoId = DB::table('cursos')->insertGetId([
                    'grado_id' => $sec->grado_id,
                    'nombre' => $nombre,
                    'codigo' => strtoupper(Str::slug($nombre, '')) . '-' . $sec->id . '-' . Str::upper(Str::random(4)),
                    'horas_semana' => $horas,
                    'descripcion' => "Curso de {$nombre}",
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                $compRows = [];
                foreach ($comps as $orden => $cnom) {
                    $compRows[] = [
                        'curso_id' => $cursoId, 'nombre' => $cnom,
                        'descripcion' => null, 'orden' => $orden + 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
                DB::table('competencias')->insert($compRows);

                $docenteId = $docentes[$docKeys[$rotIdx % $docCount]];
                $rotIdx++;

                $scId = DB::table('seccion_curso')->insertGetId([
                    'seccion_id' => $sec->id,
                    'curso_id' => $cursoId,
                    'docente_id' => $docenteId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                // 6 cursos => 1 bloque/dia de Lun(1) a Vie(5), hora = 08:00 + cursoIdx.
                // curso0 Lun 08-09, curso1 Mar 08-09 ... pero queremos cargar la
                // semana: repartimos por dia y vamos subiendo hora cuando se repite dia.
                $dia = ($cursoIdx % 5) + 1;       // 1..5
                $franja = intdiv($cursoIdx, 5);    // 0,1,...
                $horaInicio = 8 + $franja;         // 08:00, 09:00...
                $horaFin = $horaInicio + 1;

                DB::table('horarios')->insert([
                    'seccion_curso_id' => $scId,
                    'dia_semana' => $dia,
                    'hora_inicio' => sprintf('%02d:00:00', $horaInicio),
                    'hora_fin' => sprintf('%02d:00:00', $horaFin),
                    'aula' => 'Aula ' . $sec->grado_id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                $cursoIdx++;
            }

            DB::table('secciones')->where('id', $sec->id)
                ->update(['docente_tutor_id' => $docentes[$docKeys[0]] ?? null]);
        }
    }

    private function nombresPool(): array
    {
        return [
            'Pedro', 'Lucía', 'Diego', 'Camila', 'Mateo', 'Valentina', 'Sebastián',
            'Fernanda', 'Joaquín', 'Daniela', 'Bruno', 'Antonia', 'Gabriel', 'Renata',
            'Tomás', 'Isabela', 'Emilio', 'Mía', 'Adrián', 'Ariana', 'Santiago', 'Fátima',
            'Nicolás', 'Valeria',
        ];
    }

    private function apellidosPool(): array
    {
        return [
            'Salazar', 'Vega', 'Rojas', 'Núñez', 'Castro', 'Ríos', 'Paredes', 'León',
            'Mora', 'Cruz', 'Soto', 'Herrera', 'Campos', 'Ortiz', 'Vargas', 'Reyes',
            'Guzmán', 'Fuentes', 'Cordova', 'Chávez', 'Aguilar', 'Mamani', 'Quispe',
            'Huamán', 'Flores', 'Ramos',
        ];
    }

    private function seedEstudiantesMatriculasPagos(int $periodoId): void
    {
        $nombres = $this->nombresPool();
        $apellidos = $this->apellidosPool();
        $nC = count($nombres);
        $aC = count($apellidos);

        $secciones = DB::table('secciones')->where('periodo_id', $periodoId)->get(['id', 'grado_id']);

        // 1) Insertar estudiantes en chunks. Guardamos índice global para nombres/dni.
        $estChunk = [];
        $global = 0;
        // Mapa: posición -> [seccion_id, grado] para luego matricular
        $plan = []; // [ ['dni'=>..., 'seccion_id'=>...] ]

        foreach ($secciones as $sec) {
            for ($k = 0; $k < self::ALUMNOS_POR_SECCION; $k++) {
                $nom = $nombres[$global % $nC];
                $ape = $apellidos[($global * 3 + 7) % $aC] . ' ' . $apellidos[($global * 5 + 2) % $aC];
                $dni = str_pad((string) (80000000 + $global), 8, '0', STR_PAD_LEFT);

                $estChunk[] = [
                    'codigo_estudiante' => 'EST-2026-' . str_pad((string) ($global + 1), 5, '0', STR_PAD_LEFT),
                    'dni' => $dni,
                    'pin' => Hash::make(self::PIN_DEMO),
                    'nombres' => $nom,
                    'apellidos' => $ape,
                    'fecha_nacimiento' => sprintf('20%02d-%02d-%02d', 8 + ($global % 8), ($global % 12) + 1, ($global % 28) + 1),
                    'sexo' => $global % 2 === 0 ? 'M' : 'F',
                    'direccion' => 'Av. Demo ' . ($global + 1),
                    'estado' => 'activo',
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $plan[] = ['dni' => $dni, 'seccion_id' => $sec->id];

                if (count($estChunk) >= self::CHUNK) {
                    DB::table('estudiantes')->insert($estChunk);
                    $estChunk = [];
                }
                $global++;
            }
        }
        if ($estChunk) {
            DB::table('estudiantes')->insert($estChunk);
        }

        // 2) Mapear dni -> estudiante_id (incluye los recién insertados).
        $dniToId = DB::table('estudiantes')->pluck('id', 'dni');

        // 3) Insertar matriculas en chunks.
        $matChunk = [];
        $matPlan = []; // dni => seccion_id para luego ligar pagos
        foreach ($plan as $p) {
            $estId = $dniToId[$p['dni']] ?? null;
            if (! $estId) continue;
            $matChunk[] = [
                'estudiante_id' => $estId,
                'periodo_id' => $periodoId,
                'seccion_id' => $p['seccion_id'],
                'estado' => 'activa',
                'fecha_matricula' => now()->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (count($matChunk) >= self::CHUNK) {
                DB::table('matriculas')->insert($matChunk);
                $matChunk = [];
            }
        }
        if ($matChunk) {
            DB::table('matriculas')->insert($matChunk);
        }

        // 4) Estudiante Luis conservado: matricularlo en grado 10 sección A.
        $luis = DB::table('estudiantes')->where('dni', self::EST_DNI_KEEP)->first();
        if ($luis) {
            $secA = DB::table('secciones')->where('grado_id', 10)
                ->where('periodo_id', $periodoId)->where('nombre', 'A')->value('id');
            if ($secA && ! DB::table('matriculas')->where('estudiante_id', $luis->id)->exists()) {
                DB::table('matriculas')->insert([
                    'estudiante_id' => $luis->id,
                    'periodo_id' => $periodoId,
                    'seccion_id' => $secA,
                    'estado' => 'activa',
                    'fecha_matricula' => now()->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // 5) Pagos por matrícula en chunks (TODAS las matriculas del periodo).
        $this->seedPagos($periodoId);
    }

    private function seedPagos(int $periodoId): void
    {
        $matriculas = DB::table('matriculas')->where('periodo_id', $periodoId)->pluck('id')->all();
        $pagoChunk = [];
        $i = 0;
        foreach ($matriculas as $matId) {
            // 1 matrícula pagada.
            $pagoChunk[] = $this->pagoRow($matId, 'matricula', 'Matrícula 2026', 150.00, null,
                now()->toDateString(), now()->toDateString(), 'efectivo', 'pagado');

            // 10 pensiones (mes 3-12) con estados mixtos repartidos por índice.
            for ($mes = 3; $mes <= 12; $mes++) {
                $sel = ($i + $mes) % 4; // 0,1 => pagado (50%); 2 => pendiente (25%); 3 => vencido (25%)
                if ($sel <= 1) {
                    $estado = 'pagado';
                    $fechaVenc = now()->subDays(10)->toDateString();
                    $fechaPago = now()->subDays(5)->toDateString();
                    $metodo = ['efectivo', 'yape', 'transferencia', 'plin'][$i % 4];
                } elseif ($sel === 2) {
                    $estado = 'pendiente';
                    $fechaVenc = now()->addDays(20)->toDateString();
                    $fechaPago = null;
                    $metodo = null;
                } else {
                    $estado = 'vencido';
                    $fechaVenc = '2026-03-15';
                    $fechaPago = null;
                    $metodo = null;
                }
                $pagoChunk[] = $this->pagoRow($matId, 'pension', 'Pensión mes ' . $mes, 200.00, $mes,
                    $fechaVenc, $fechaPago, $metodo, $estado);
            }

            if (count($pagoChunk) >= self::CHUNK) {
                DB::table('pagos')->insert($pagoChunk);
                $pagoChunk = [];
            }
            $i++;
        }
        if ($pagoChunk) {
            DB::table('pagos')->insert($pagoChunk);
        }
    }

    /** Fila de pago con EXACTAMENTE las mismas columnas siempre (evita bug de DemoSeeder). */
    private function pagoRow(int $matId, string $concepto, string $descripcion, float $monto,
        ?int $mes, string $fechaVenc, ?string $fechaPago, ?string $metodo, string $estado): array
    {
        return [
            'matricula_id' => $matId,
            'concepto' => $concepto,
            'descripcion' => $descripcion,
            'monto' => $monto,
            'mes' => $mes,
            'fecha_vencimiento' => $fechaVenc,
            'fecha_pago' => $fechaPago,
            'metodo' => $metodo,
            'estado' => $estado,
            'comprobante_url' => null,
            'observaciones' => null,
            'registrado_por' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /** Notas: para cada seccion_curso, bimestres 1 y 2, cada matrícula × competencia. */
    private function seedNotas(int $periodoId): void
    {
        $bimestres = DB::table('bimestres')->where('periodo_id', $periodoId)
            ->orderBy('orden')->limit(2)->pluck('id')->all();
        if (! $bimestres) return;

        // Distribución con ~25% C: AD, A, B, C  (índice % 4 -> C en 1 de 4)
        $notas = ['A', 'AD', 'B', 'C'];

        $scList = DB::table('seccion_curso')->get(['id', 'seccion_id', 'curso_id']);

        // Cache matriculas por seccion.
        $matBySeccion = [];
        foreach (DB::table('matriculas')->where('periodo_id', $periodoId)->get(['id', 'seccion_id']) as $m) {
            $matBySeccion[$m->seccion_id][] = $m->id;
        }
        // Cache competencias por curso.
        $compByCurso = [];
        foreach (DB::table('competencias')->get(['id', 'curso_id']) as $c) {
            $compByCurso[$c->curso_id][] = $c->id;
        }

        $chunk = [];
        foreach ($scList as $sc) {
            $matriculas = $matBySeccion[$sc->seccion_id] ?? [];
            $comps = $compByCurso[$sc->curso_id] ?? [];
            foreach ($matriculas as $idx => $matId) {
                foreach ($comps as $cIdx => $compId) {
                    foreach ($bimestres as $bIdx => $bimestreId) {
                        $pick = ($idx + $cIdx + $bIdx + $compId) % 4;
                        $chunk[] = [
                            'matricula_id' => $matId,
                            'seccion_curso_id' => $sc->id,
                            'competencia_id' => $compId,
                            'bimestre_id' => $bimestreId,
                            'nota' => $notas[$pick],
                            'conclusion_descriptiva' => null,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                        if (count($chunk) >= self::CHUNK_NOTAS) {
                            DB::table('calificaciones')->insert($chunk);
                            $chunk = [];
                        }
                    }
                }
            }
        }
        if ($chunk) {
            DB::table('calificaciones')->insert($chunk);
        }
    }

    /** Asistencia: para cada seccion_curso, 5 fechas, cada matrícula estado mixto. */
    private function seedAsistencias(): void
    {
        $fechas = ['2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06'];
        $estados = ['presente', 'presente', 'tarde', 'falta', 'justificada'];

        $scList = DB::table('seccion_curso')->get(['id', 'seccion_id']);
        $matBySeccion = [];
        foreach (DB::table('matriculas')->get(['id', 'seccion_id']) as $m) {
            $matBySeccion[$m->seccion_id][] = $m->id;
        }

        $chunk = [];
        foreach ($scList as $sc) {
            $matriculas = $matBySeccion[$sc->seccion_id] ?? [];
            foreach ($matriculas as $idx => $matId) {
                foreach ($fechas as $fIdx => $fecha) {
                    $chunk[] = [
                        'matricula_id' => $matId,
                        'seccion_curso_id' => $sc->id,
                        'fecha' => $fecha,
                        'estado' => $estados[($idx + $fIdx) % 5],
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                    if (count($chunk) >= self::CHUNK_NOTAS) {
                        DB::table('asistencias')->insert($chunk);
                        $chunk = [];
                    }
                }
            }
        }
        if ($chunk) {
            DB::table('asistencias')->insert($chunk);
        }
    }

    /** Pagos a docentes: 3 filas por docente (meses 3,4,5), estados mixtos. */
    private function seedPagosDocentes(array $docentes, int $periodoId): void
    {
        $rows = [];
        $i = 0;
        foreach ($docentes as $docId) {
            foreach ([3, 4, 5] as $j => $mes) {
                // mixto: pagado/pendiente
                $pagado = (($i + $j) % 2) === 0;
                $rows[] = [
                    'docente_id' => $docId,
                    'periodo_id' => $periodoId,
                    'concepto' => 'sueldo',
                    'descripcion' => 'Sueldo mes ' . $mes,
                    'monto' => 2500.00,
                    'mes' => $mes,
                    'anio' => 2026,
                    'fecha_pago' => $pagado ? now()->subDays(3)->toDateString() : null,
                    'metodo' => $pagado ? 'transferencia' : null,
                    'estado' => $pagado ? 'pagado' : 'pendiente',
                    'observaciones' => null,
                    'registrado_por' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            $i++;
        }
        DB::table('pagos_docentes')->insert($rows);
    }
}
