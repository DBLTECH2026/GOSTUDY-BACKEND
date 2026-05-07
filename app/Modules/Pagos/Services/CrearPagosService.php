<?php

namespace App\Modules\Pagos\Services;

use App\Models\Matricula;
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
 */
class CrearPagosService
{
    public const MONTO_MATRICULA = 350.00;
    public const MONTO_PENSION   = 250.00;

    public const MES_INICIO_PENSIONES = 3;
    public const MES_FIN_PENSIONES    = 12;
    public const DIA_VENCIMIENTO      = 5;
    public const DIAS_PARA_PAGAR_MATRICULA = 7;

    public function crearPagosParaMatricula(Matricula $matricula): Collection
    {
        if (Pago::porMatricula($matricula->id)->exists()) {
            return collect();
        }

        return DB::transaction(function () use ($matricula) {
            $anio = $this->resolverAnio($matricula);

            $pagos = collect();
            $pagos->push($this->crearPagoMatricula($matricula, $anio));

            for ($mes = self::MES_INICIO_PENSIONES; $mes <= self::MES_FIN_PENSIONES; $mes++) {
                $pagos->push($this->crearPagoPension($matricula, $anio, $mes));
            }

            return $pagos;
        });
    }

    private function crearPagoMatricula(Matricula $matricula, int $anio): Pago
    {
        $fechaBase = $matricula->fecha_matricula instanceof Carbon
            ? $matricula->fecha_matricula->copy()
            : Carbon::parse($matricula->fecha_matricula);

        return Pago::create([
            'matricula_id'      => $matricula->id,
            'concepto'          => Pago::CONCEPTO_MATRICULA,
            'descripcion'       => "Matrícula {$anio}",
            'monto'             => self::MONTO_MATRICULA,
            'mes'               => null,
            'fecha_vencimiento' => $fechaBase->addDays(self::DIAS_PARA_PAGAR_MATRICULA),
            'estado'            => Pago::ESTADO_PENDIENTE,
        ]);
    }

    private function crearPagoPension(Matricula $matricula, int $anio, int $mes): Pago
    {
        $vencimiento = Carbon::create($anio, $mes, self::DIA_VENCIMIENTO);
        $nombreMes   = $vencimiento->locale('es')->translatedFormat('F');

        return Pago::create([
            'matricula_id'      => $matricula->id,
            'concepto'          => Pago::CONCEPTO_PENSION,
            'descripcion'       => 'Pensión ' . ucfirst($nombreMes) . " {$anio}",
            'monto'             => self::MONTO_PENSION,
            'mes'               => $mes,
            'fecha_vencimiento' => $vencimiento,
            'estado'            => Pago::ESTADO_PENDIENTE,
        ]);
    }

    private function resolverAnio(Matricula $matricula): int
    {
        if (isset($matricula->periodo) && isset($matricula->periodo->anio)) {
            return (int) $matricula->periodo->anio;
        }

        $fecha = $matricula->fecha_matricula instanceof Carbon
            ? $matricula->fecha_matricula
            : Carbon::parse($matricula->fecha_matricula);

        return (int) $fecha->format('Y');
    }
}
