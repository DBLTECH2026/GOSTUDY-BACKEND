<?php

namespace App\Modules\Pagos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PagoDocente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PagoDocenteController extends Controller
{
    /**
     * GET /api/v1/pagos-docentes?docente_id=&mes=&estado=
     * Listado admin de pagos a docentes con el nombre del docente embebido.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('pagos_docentes as pd')
            ->join('docentes as d', 'd.id', '=', 'pd.docente_id')
            ->join('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->select([
                'pd.id', 'pd.docente_id', 'pd.concepto', 'pd.descripcion', 'pd.monto',
                'pd.mes', 'pd.anio', 'pd.estado', 'pd.fecha_pago', 'pd.metodo',
                'u.nombres as docente_nombres', 'u.apellidos as docente_apellidos',
            ])
            ->orderBy('pd.anio', 'desc')
            ->orderBy('pd.mes', 'desc');

        if ($request->filled('docente_id')) {
            $query->where('pd.docente_id', $request->integer('docente_id'));
        }
        if ($request->filled('mes')) {
            $query->where('pd.mes', $request->integer('mes'));
        }
        if ($request->filled('estado')) {
            $query->where('pd.estado', $request->string('estado'));
        }

        $items = $query->get()->map(fn ($r) => [
            'id'             => (int) $r->id,
            'docente_id'     => (int) $r->docente_id,
            'docente_nombre' => trim("{$r->docente_nombres} {$r->docente_apellidos}"),
            'concepto'       => $r->concepto,
            'descripcion'    => $r->descripcion,
            'monto'          => (float) $r->monto,
            'mes'            => $r->mes !== null ? (int) $r->mes : null,
            'anio'           => (int) $r->anio,
            'estado'         => $r->estado,
            'fecha_pago'     => $r->fecha_pago,
            'metodo'         => $r->metodo,
        ]);

        return response()->json(['data' => $items]);
    }

    /**
     * GET /api/v1/pagos-docentes/docentes
     * Lista de docentes para el selector del formulario.
     */
    public function docentes(): JsonResponse
    {
        $items = DB::table('docentes as d')
            ->join('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->where('u.rol', 'docente')
            ->orderBy('u.apellidos')
            ->orderBy('u.nombres')
            ->select(['d.id', 'u.nombres', 'u.apellidos'])
            ->get()
            ->map(fn ($r) => [
                'id'     => (int) $r->id,
                'nombre' => trim("{$r->nombres} {$r->apellidos}"),
            ]);

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/v1/pagos-docentes
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validar($request);
        $data['registrado_por'] = $request->user()?->id;

        $pago = PagoDocente::create($data);

        return response()->json([
            'message' => 'Pago registrado.',
            'data'    => $pago,
        ], 201);
    }

    /**
     * PUT /api/v1/pagos-docentes/{pagoDocente}
     */
    public function update(Request $request, PagoDocente $pagoDocente): JsonResponse
    {
        $data = $this->validar($request);

        $pagoDocente->update($data);

        return response()->json([
            'message' => 'Pago actualizado.',
            'data'    => $pagoDocente,
        ]);
    }

    /**
     * PUT /api/v1/pagos-docentes/{pagoDocente}/pagar
     */
    public function marcarPagado(Request $request, PagoDocente $pagoDocente): JsonResponse
    {
        $data = $request->validate([
            'metodo' => ['nullable', 'in:efectivo,transferencia,yape,plin,otro'],
        ]);

        $pagoDocente->update([
            'estado'     => 'pagado',
            'fecha_pago' => Carbon::now()->toDateString(),
            'metodo'     => $data['metodo'] ?? $pagoDocente->metodo,
        ]);

        return response()->json([
            'message' => 'Marcado como pagado.',
            'data'    => $pagoDocente,
        ]);
    }

    /**
     * DELETE /api/v1/pagos-docentes/{pagoDocente}
     */
    public function destroy(PagoDocente $pagoDocente): JsonResponse
    {
        $pagoDocente->delete();

        return response()->json(['message' => 'Eliminado.']);
    }

    /**
     * Reglas de validación compartidas por store/update.
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'docente_id'    => ['required', 'integer', 'exists:docentes,id'],
            'concepto'      => ['required', 'in:sueldo,bono,otros'],
            'descripcion'   => ['required', 'string', 'max:150'],
            'monto'         => ['required', 'numeric', 'min:0'],
            'anio'          => ['required', 'integer'],
            'mes'           => ['nullable', 'integer', 'between:1,12'],
            'metodo'        => ['nullable', 'in:efectivo,transferencia,yape,plin,otro'],
            'estado'        => ['nullable', 'in:pendiente,pagado,anulado'],
            'observaciones' => ['nullable', 'string'],
        ]);
    }
}
