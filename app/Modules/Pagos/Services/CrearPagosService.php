<?php

namespace App\Modules\Pagos\Services;

use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * GOSTUDY — Persona C · Pagos.
 *
 * Genera el plan de cobranza al aprobarse una matrícula:
 *   - 1 pago de concepto "matricula" (vence al día +7 de la fecha de matrícula).
 *   - 10 pensiones (mes 3 a 12, vencimiento día 5 de cada mes).
 *
 * Es idempotente: si ya hay pagos para la matrícula, no crea duplicados.
 *
 * Toma primitivos en vez de un modelo Matricula para no acoplarse a la
 * implementación de Persona B y poder testearse en aislamiento.
 */
class CrearPagosService
{
    public const MONTO_MATRICULA = 350.00;
    public const MONTO_PENSION   = 250.00;

    public const MES_INICIO_PENSIONES = 3;
    public const MES_FIN_PENSIONES    = 12;
    public const DIA_VENCIMIENTO      = 5;
    public const DIAS_PARA_PAGAR_MATRICULA = 7;

    /**
     * @param int           $matriculaId      ID de la matrícula recién aprobada.
     * @param Carbon|string $fechaMatricula   Fecha en que se aprobó la matrícula.
     * @param int|null      $anio             Año académico; si null se infiere de fechaMatricula.
     */
    public function crearPagosParaMatricula(
        int $matriculaId,
        Carbon|string $fechaMatricula,
        ?int $anio = null,
    ): Collection {
        if (Pago::porMatricula($matriculaId)->exists()) {
            return collect();
        }

        $fecha = $fechaMatricula instanceof Carbon
            ? $fechaMatricula->copy()
            : Carbon::parse($fechaMatricula);
        $anio ??= (int) $fecha->format('Y');

        return DB::transaction(function () use ($matriculaId, $fecha, $anio) {
            $pagos = collect();
            $pagos->push($this->crearPagoMatricula($matriculaId, $fecha, $anio));

            for ($mes = self::MES_INICIO_PENSIONES; $mes <= self::MES_FIN_PENSIONES; $mes++) {
                $pagos->push($this->crearPagoPension($matriculaId, $anio, $mes));
            }

            return $pagos;
        });
    }

    private function crearPagoMatricula(int $matriculaId, Carbon $fecha, int $anio): Pago
    {
        return Pago::create([
            'matricula_id'      => $matriculaId,
            'concepto'          => Pago::CONCEPTO_MATRICULA,
            'descripcion'       => "Matrícula {$anio}",
            'monto'             => self::MONTO_MATRICULA,
            'mes'               => null,
            'fecha_vencimiento' => $fecha->copy()->addDays(self::DIAS_PARA_PAGAR_MATRICULA),
            'estado'            => Pago::ESTADO_PENDIENTE,
        ]);
    }

    private function crearPagoPension(int $matriculaId, int $anio, int $mes): Pago
    {
        $vencimiento = Carbon::create($anio, $mes, self::DIA_VENCIMIENTO);
        $nombreMes   = $vencimiento->locale('es')->translatedFormat('F');

        return Pago::create([
            'matricula_id'      => $matriculaId,
            'concepto'          => Pago::CONCEPTO_PENSION,
            'descripcion'       => 'Pensión ' . ucfirst($nombreMes) . " {$anio}",
            'monto'             => self::MONTO_PENSION,
            'mes'               => $mes,
            'fecha_vencimiento' => $vencimiento,
            'estado'            => Pago::ESTADO_PENDIENTE,
        ]);
    }
}
