<?php

namespace App\Modules\Pagos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Modules\Pagos\Resources\EstadoCuentaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadoCuentaController extends Controller
{
    /**
     * GET /api/v1/estudiantes/{estudiante}/estado-cuenta
     *
     * Devuelve todos los pagos del estudiante (a través de su matrícula activa)
     * con totales por estado.
     */
    public function show(Request $request, int $estudianteId): EstadoCuentaResource
    {
        $estudiante = DB::table('estudiantes')
            ->where('id', $estudianteId)
            ->first(['id', 'codigo_estudiante', 'nombres', 'apellidos', 'dni']);

        abort_if($estudiante === null, 404, 'Estudiante no encontrado.');

        $matriculaIds = DB::table('matriculas')
            ->where('estudiante_id', $estudianteId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $pagos = Pago::whereIn('matricula_id', $matriculaIds)
            ->orderBy('fecha_vencimiento')
            ->get();

        return new EstadoCuentaResource([
            'estudiante' => $estudiante,
            'pagos'      => $pagos,
        ]);
    }
}
