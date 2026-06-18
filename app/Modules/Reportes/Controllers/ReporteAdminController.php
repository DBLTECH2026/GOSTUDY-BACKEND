<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GOSTUDY — Persona C · Reportes administrativos (BLOQUE 4.3).
 *
 * Deuda de alumnos, pagos atrasados, recaudación y pagos a docentes.
 * Todos devuelven siempre ['data' => ...].
 */
class ReporteAdminController extends Controller
{
    /**
     * GET /api/v1/reportes/deuda?seccion_id=&periodo_id=
     *
     * Alumnos con pagos pendientes. Devuelve filas detalladas + total adeudado.
     */
    public function deuda(Request $request): JsonResponse
    {
        $query = DB::table('pagos as p')
            ->join('matriculas as m', 'm.id', '=', 'p.matricula_id')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->where('p.estado', Pago::ESTADO_PENDIENTE)
            ->whereNull('m.deleted_at');

        if ($request->filled('seccion_id')) {
            $query->where('m.seccion_id', $request->integer('seccion_id'));
        }

        if ($request->filled('periodo_id')) {
            $query->where('m.periodo_id', $request->integer('periodo_id'));
        }

        $items = (clone $query)
            ->selectRaw("
                TRIM(CONCAT(e.nombres, ' ', e.apellidos)) as estudiante,
                e.codigo_estudiante as codigo,
                g.nombre as grado,
                s.nombre as seccion,
                p.concepto,
                p.descripcion,
                p.monto,
                p.fecha_vencimiento
            ")
            ->orderBy('p.fecha_vencimiento')
            ->get();

        $total = (float) (clone $query)->sum('p.monto');

        return response()->json([
            'data' => [
                'items' => $items,
                'total' => $total,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/atrasados?seccion_id=&periodo_id=
     *
     * Pagos vencidos: estado='vencido' OR (pendiente AND fecha_vencimiento < hoy).
     */
    public function atrasados(Request $request): JsonResponse
    {
        $hoy = now()->toDateString();

        $query = DB::table('pagos as p')
            ->join('matriculas as m', 'm.id', '=', 'p.matricula_id')
            ->join('estudiantes as e', 'e.id', '=', 'm.estudiante_id')
            ->join('secciones as s', 's.id', '=', 'm.seccion_id')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->whereNull('m.deleted_at')
            ->where(function ($q) use ($hoy) {
                $q->where('p.estado', Pago::ESTADO_VENCIDO)
                  ->orWhere(function ($q2) use ($hoy) {
                      $q2->where('p.estado', Pago::ESTADO_PENDIENTE)
                         ->where('p.fecha_vencimiento', '<', $hoy);
                  });
            });

        if ($request->filled('seccion_id')) {
            $query->where('m.seccion_id', $request->integer('seccion_id'));
        }

        if ($request->filled('periodo_id')) {
            $query->where('m.periodo_id', $request->integer('periodo_id'));
        }

        $items = (clone $query)
            ->selectRaw("
                TRIM(CONCAT(e.nombres, ' ', e.apellidos)) as estudiante,
                e.codigo_estudiante as codigo,
                g.nombre as grado,
                s.nombre as seccion,
                p.concepto,
                p.monto,
                p.fecha_vencimiento,
                DATEDIFF(?, p.fecha_vencimiento) as dias_atraso
            ", [$hoy])
            ->orderByDesc(DB::raw('DATEDIFF("' . $hoy . '", p.fecha_vencimiento)'))
            ->get();

        $total = (float) (clone $query)->sum('p.monto');

        return response()->json([
            'data' => [
                'items' => $items,
                'total' => $total,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/recaudacion?periodo_id=
     *
     * Totales por estado y por concepto + totales globales.
     */
    public function recaudacion(Request $request): JsonResponse
    {
        $query = DB::table('pagos as p')
            ->join('matriculas as m', 'm.id', '=', 'p.matricula_id')
            ->whereNull('m.deleted_at');

        if ($request->filled('periodo_id')) {
            $query->where('m.periodo_id', $request->integer('periodo_id'));
        }

        $porEstado = (clone $query)
            ->selectRaw('p.estado, SUM(p.monto) as total, COUNT(*) as cantidad')
            ->groupBy('p.estado')
            ->get();

        $porConcepto = (clone $query)
            ->selectRaw('p.concepto, SUM(p.monto) as total, COUNT(*) as cantidad')
            ->groupBy('p.concepto')
            ->get();

        $totalPagado    = (float) (clone $query)->where('p.estado', Pago::ESTADO_PAGADO)->sum('p.monto');
        $totalPendiente = (float) (clone $query)->where('p.estado', Pago::ESTADO_PENDIENTE)->sum('p.monto');
        $totalVencido   = (float) (clone $query)->where('p.estado', Pago::ESTADO_VENCIDO)->sum('p.monto');

        return response()->json([
            'data' => [
                'por_estado'      => $porEstado,
                'por_concepto'    => $porConcepto,
                'total_pagado'    => $totalPagado,
                'total_pendiente' => $totalPendiente,
                'total_vencido'   => $totalVencido,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/pagos-docentes-reporte?mes=&estado=
     *
     * Pagos a docentes con nombre del docente.
     */
    public function pagosDocentesReporte(Request $request): JsonResponse
    {
        $query = DB::table('pagos_docentes as pd')
            ->join('docentes as d', 'd.id', '=', 'pd.docente_id')
            ->join('usuarios as u', 'u.id', '=', 'd.usuario_id');

        if ($request->filled('mes')) {
            $query->where('pd.mes', $request->integer('mes'));
        }

        if ($request->filled('estado')) {
            $query->where('pd.estado', $request->string('estado'));
        }

        $items = (clone $query)
            ->selectRaw("
                TRIM(CONCAT(u.nombres, ' ', u.apellidos)) as docente,
                pd.concepto,
                pd.descripcion,
                pd.monto,
                pd.mes,
                pd.anio,
                pd.estado,
                pd.fecha_pago
            ")
            ->orderBy('pd.anio')
            ->orderBy('pd.mes')
            ->get();

        $totalPagado    = (float) (clone $query)->where('pd.estado', 'pagado')->sum('pd.monto');
        $totalPendiente = (float) (clone $query)->where('pd.estado', 'pendiente')->sum('pd.monto');

        return response()->json([
            'data' => [
                'items'           => $items,
                'total_pagado'    => $totalPagado,
                'total_pendiente' => $totalPendiente,
            ],
        ]);
    }
}
