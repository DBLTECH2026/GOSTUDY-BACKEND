<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * GOSTUDY — Persona C · Reportes.
 */
class ReporteController extends Controller
{
    /**
     * GET /api/v1/reportes/dashboard
     * Counts agregados para la pantalla principal del admin.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $inicioMes = now()->startOfMonth()->toDateString();

        $inscripcionesPendientes = DB::table('inscripciones')
            ->where('estado', 'pendiente')
            ->count();

        $matriculasDelMes = DB::table('matriculas')
            ->where('fecha_matricula', '>=', $inicioMes)
            ->whereNull('deleted_at')
            ->count();

        $pagosDelMes = (float) DB::table('pagos')
            ->where('estado', Pago::ESTADO_PAGADO)
            ->where('fecha_pago', '>=', $inicioMes)
            ->sum('monto');

        $pagosVencidos = DB::table('pagos')
            ->where('estado', Pago::ESTADO_VENCIDO)
            ->count();

        $totalEstudiantes = DB::table('estudiantes')->whereNull('deleted_at')->count();
        $totalDocentes    = DB::table('docentes')->count();

        return response()->json([
            'data' => [
                'inscripciones_pendientes' => $inscripcionesPendientes,
                'matriculas_del_mes'       => $matriculasDelMes,
                'pagos_del_mes'            => $pagosDelMes,
                'pagos_vencidos'           => $pagosVencidos,
                'total_estudiantes'        => $totalEstudiantes,
                'total_docentes'           => $totalDocentes,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/inscripciones?periodo_id=&estado=
     */
    public function inscripciones(Request $request): JsonResponse
    {
        $query = DB::table('inscripciones');

        if ($request->filled('periodo_id')) {
            $query->where('periodo_id', $request->integer('periodo_id'));
        }

        $totales = (clone $query)
            ->selectRaw("estado, COUNT(*) as total")
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->all();

        $porMes = (clone $query)
            ->selectRaw("DATE_FORMAT(fecha_inscripcion, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $porNivel = (clone $query)
            ->join('niveles', 'niveles.id', '=', 'inscripciones.nivel_id')
            ->selectRaw('niveles.nombre as nivel, COUNT(*) as total')
            ->groupBy('niveles.nombre')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'data' => [
                'totales_por_estado' => $totales,
                'por_mes'            => $porMes,
                'por_nivel'          => $porNivel,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/matriculas-por-seccion?periodo_id=
     */
    public function matriculasPorSeccion(Request $request): JsonResponse
    {
        $query = DB::table('secciones as s')
            ->join('grados as g', 'g.id', '=', 's.grado_id')
            ->join('niveles as n', 'n.id', '=', 'g.nivel_id')
            ->leftJoin('matriculas as m', function ($j) {
                $j->on('m.seccion_id', '=', 's.id')
                  ->where('m.estado', 'activa')
                  ->whereNull('m.deleted_at');
            })
            ->selectRaw('
                s.id as seccion_id,
                n.nombre as nivel,
                g.nombre as grado,
                s.nombre as seccion,
                s.capacidad,
                COUNT(m.id) as matriculados
            ')
            ->groupBy('s.id', 'n.nombre', 'g.nombre', 's.nombre', 's.capacidad');

        if ($request->filled('periodo_id')) {
            $query->where('s.periodo_id', $request->integer('periodo_id'));
        }

        $secciones = $query->get()->map(function ($row) {
            $row->ocupacion_porcentaje = $row->capacidad > 0
                ? round(($row->matriculados / $row->capacidad) * 100, 1)
                : 0.0;
            return $row;
        });

        return response()->json(['data' => $secciones]);
    }

    /**
     * GET /api/v1/reportes/pagos-por-periodo?periodo_id=
     */
    public function pagosPorPeriodo(Request $request): JsonResponse
    {
        $query = Pago::query();

        if ($request->filled('periodo_id')) {
            $query->whereHas('matricula', fn ($q) => $q->where('periodo_id', $request->integer('periodo_id')));
        }

        $totalFacturado = (clone $query)->sum('monto');
        $totalPagado    = (clone $query)->where('estado', Pago::ESTADO_PAGADO)->sum('monto');
        $totalPendiente = (clone $query)->where('estado', Pago::ESTADO_PENDIENTE)->sum('monto');
        $totalVencido   = (clone $query)->where('estado', Pago::ESTADO_VENCIDO)->sum('monto');

        $porMes = (clone $query)
            ->where('estado', Pago::ESTADO_PAGADO)
            ->selectRaw("DATE_FORMAT(fecha_pago, '%Y-%m') as mes, SUM(monto) as total")
            ->whereNotNull('fecha_pago')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $porConcepto = (clone $query)
            ->selectRaw('concepto, SUM(monto) as total, COUNT(*) as cantidad')
            ->groupBy('concepto')
            ->get();

        return response()->json([
            'data' => [
                'totales' => [
                    'facturado' => (float) $totalFacturado,
                    'pagado'    => (float) $totalPagado,
                    'pendiente' => (float) $totalPendiente,
                    'vencido'   => (float) $totalVencido,
                ],
                'recaudacion_por_mes' => $porMes,
                'por_concepto'        => $porConcepto,
            ],
        ]);
    }

    /**
     * GET /api/v1/reportes/pdf?ruta=/reportes/acta-notas&...
     * Genera el PDF del reporte vía Browserless. Si no hay token
     * configurado, responde 409 para que el front use el fallback jsPDF.
     */
    public function pdf(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'ruta' => ['required', 'string'],
        ]);

        $pdfService = new PdfService();

        if (! $pdfService->configurado()) {
            return response()->json(['fallback' => 'jspdf'], 409);
        }

        $ruta = $validated['ruta'];
        $otrosParams = $request->except('ruta');
        $otrosParams['print'] = '1';

        $url = rtrim((string) config('services.frontend_url'), '/')
            . '/' . ltrim($ruta, '/')
            . '?' . http_build_query($otrosParams);

        $pdf = $pdfService->fromUrl($url);

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="reporte.pdf"');
    }
}
