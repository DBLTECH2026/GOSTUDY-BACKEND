<?php

namespace App\Modules\Catalogos\Controllers;

use App\Models\Nivel;
use App\Models\PeriodoAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController
{
    public function nivelesGrados(): JsonResponse
    {
        $niveles = Nivel::with('grados')
            ->orderBy('orden')
            ->get()
            ->map(fn ($n) => [
                'id'     => $n->id,
                'nombre' => $n->nombre,
                'orden'  => $n->orden,
                'grados' => $n->grados->map(fn ($g) => [
                    'id'     => $g->id,
                    'nombre' => $g->nombre,
                    'orden'  => $g->orden,
                ])->values(),
            ]);

        return response()->json(['data' => $niveles]);
    }

    public function periodoActivo(): JsonResponse
    {
        $periodo = PeriodoAcademico::activo();

        if (! $periodo) {
            return response()->json([
                'message' => 'No hay periodo académico activo configurado.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'           => $periodo->id,
                'anio'         => $periodo->anio,
                'descripcion'  => $periodo->descripcion,
                'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
                'fecha_fin'    => $periodo->fecha_fin->toDateString(),
                'estado'       => $periodo->estado,
            ],
        ]);
    }

    /* ─── Ubigeo (RENIEC) — cascada departamento → provincia → distrito ─── */

    public function departamentos(): JsonResponse
    {
        $departamentos = DB::table('reniec_ubigeo')
            ->select('departamento')
            ->distinct()
            ->orderBy('departamento')
            ->pluck('departamento');

        return response()->json(['data' => $departamentos]);
    }

    public function provincias(Request $request): JsonResponse
    {
        $request->validate(['departamento' => ['required', 'string']]);

        $provincias = DB::table('reniec_ubigeo')
            ->select('provincia')
            ->where('departamento', $request->query('departamento'))
            ->distinct()
            ->orderBy('provincia')
            ->pluck('provincia');

        return response()->json(['data' => $provincias]);
    }

    public function distritos(Request $request): JsonResponse
    {
        $request->validate([
            'departamento' => ['required', 'string'],
            'provincia'    => ['required', 'string'],
        ]);

        $distritos = DB::table('reniec_ubigeo')
            ->select('distrito')
            ->where('departamento', $request->query('departamento'))
            ->where('provincia', $request->query('provincia'))
            ->distinct()
            ->orderBy('distrito')
            ->pluck('distrito');

        return response()->json(['data' => $distritos]);
    }
}
