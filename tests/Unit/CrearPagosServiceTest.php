<?php

namespace Tests\Unit;

use App\Models\Pago;
use App\Modules\Pagos\Services\CrearPagosService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrearPagosServiceTest extends TestCase
{
    use RefreshDatabase;

    private CrearPagosService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrearPagosService();
    }

    public function test_crea_exactamente_11_pagos(): void
    {
        $matriculaId = $this->insertarMatricula();

        $pagos = $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $this->assertCount(11, $pagos, 'debe crear 1 matrícula + 10 pensiones');
        $this->assertSame(11, Pago::porMatricula($matriculaId)->count());
    }

    public function test_primer_pago_es_matricula_y_los_otros_10_son_pensiones(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $matricula = Pago::porMatricula($matriculaId)->where('concepto', Pago::CONCEPTO_MATRICULA)->get();
        $pensiones = Pago::porMatricula($matriculaId)->where('concepto', Pago::CONCEPTO_PENSION)->get();

        $this->assertCount(1,  $matricula);
        $this->assertCount(10, $pensiones);
        $this->assertNull($matricula->first()->mes);
    }

    public function test_pensiones_cubren_meses_3_a_12(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $meses = Pago::porMatricula($matriculaId)
            ->where('concepto', Pago::CONCEPTO_PENSION)
            ->orderBy('mes')
            ->pluck('mes')
            ->all();

        $this->assertSame([3, 4, 5, 6, 7, 8, 9, 10, 11, 12], $meses);
    }

    public function test_vencimientos_son_dia_5_de_cada_mes(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $pensiones = Pago::porMatricula($matriculaId)
            ->where('concepto', Pago::CONCEPTO_PENSION)
            ->orderBy('mes')
            ->get();

        foreach ($pensiones as $p) {
            $this->assertSame(5, $p->fecha_vencimiento->day);
            $this->assertSame(2026, $p->fecha_vencimiento->year);
        }
    }

    public function test_matricula_vence_a_los_7_dias_de_la_fecha_de_matricula(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $pago = Pago::porMatricula($matriculaId)->where('concepto', Pago::CONCEPTO_MATRICULA)->first();
        $this->assertSame('2026-02-22', $pago->fecha_vencimiento->toDateString());
    }

    public function test_montos_son_correctos(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $totalFacturado = (float) Pago::porMatricula($matriculaId)->sum('monto');
        $expected = CrearPagosService::MONTO_MATRICULA + (10 * CrearPagosService::MONTO_PENSION);
        $this->assertSame($expected, $totalFacturado);
    }

    public function test_es_idempotente_no_duplica_si_corre_dos_veces(): void
    {
        $matriculaId = $this->insertarMatricula();

        $primero  = $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);
        $segundo  = $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $this->assertCount(11, $primero);
        $this->assertCount(0,  $segundo, 'la segunda corrida debe ser no-op');
        $this->assertSame(11, Pago::porMatricula($matriculaId)->count());
    }

    public function test_descripciones_estan_en_espanol(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $marzo = Pago::porMatricula($matriculaId)->where('mes', 3)->first();
        $matricula = Pago::porMatricula($matriculaId)->where('concepto', Pago::CONCEPTO_MATRICULA)->first();

        $this->assertSame('Pensión Marzo 2026', $marzo->descripcion);
        $this->assertSame('Matrícula 2026',     $matricula->descripcion);
    }

    public function test_acepta_carbon_o_string_como_fecha(): void
    {
        $matriculaId = $this->insertarMatricula();

        $pagos = $this->service->crearPagosParaMatricula($matriculaId, Carbon::parse('2026-02-15'), 2026);
        $this->assertCount(11, $pagos);
    }

    public function test_infiere_anio_de_la_fecha_si_no_se_pasa(): void
    {
        $matriculaId = $this->insertarMatricula();

        $pagos = $this->service->crearPagosParaMatricula($matriculaId, '2027-01-10');

        $this->assertCount(11, $pagos);
        $marzo = Pago::porMatricula($matriculaId)->where('mes', 3)->first();
        $this->assertSame(2027, $marzo->fecha_vencimiento->year);
    }

    public function test_todos_los_pagos_creados_quedan_en_estado_pendiente(): void
    {
        $matriculaId = $this->insertarMatricula();

        $this->service->crearPagosParaMatricula($matriculaId, '2026-02-15', 2026);

        $estados = Pago::porMatricula($matriculaId)->pluck('estado')->unique()->values()->all();
        $this->assertSame([Pago::ESTADO_PENDIENTE], $estados);
    }

    /**
     * Crea fixtures mínimos en la BD (estudiante, periodo, nivel, grado, sección)
     * para satisfacer las FKs de la tabla matriculas, e inserta una matrícula.
     */
    private function insertarMatricula(): int
    {
        $estudianteId = DB::table('estudiantes')->insertGetId([
            'codigo_estudiante' => 'EST-TEST-' . uniqid(),
            'dni'               => substr((string) (10000000 + random_int(0, 89999999)), 0, 8),
            'pin'               => bcrypt('123456'),
            'nombres'           => 'Test',
            'apellidos'         => 'Alumno',
            'fecha_nacimiento'  => '2015-01-01',
            'sexo'              => 'M',
            'direccion'         => 'Calle test 123',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $periodoId = DB::table('periodos_academicos')->insertGetId([
            'anio'         => 2026,
            'descripcion'  => 'Periodo 2026-I',
            'fecha_inicio' => '2026-03-01',
            'fecha_fin'    => '2026-12-15',
            'estado'       => 'activo',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $nivelId = DB::table('niveles')->insertGetId([
            'nombre' => 'Primaria', 'orden' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $gradoId = DB::table('grados')->insertGetId([
            'nivel_id' => $nivelId, 'nombre' => '3ro', 'orden' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $seccionId = DB::table('secciones')->insertGetId([
            'grado_id'   => $gradoId,
            'periodo_id' => $periodoId,
            'nombre'     => 'A',
            'capacidad'  => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('matriculas')->insertGetId([
            'estudiante_id'   => $estudianteId,
            'periodo_id'      => $periodoId,
            'seccion_id'      => $seccionId,
            'fecha_matricula' => '2026-02-15',
            'estado'          => 'activa',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
