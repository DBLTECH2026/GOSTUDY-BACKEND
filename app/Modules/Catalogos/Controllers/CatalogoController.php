<?php

namespace App\Modules\Catalogos\Controllers;

use App\Models\Nivel;
use App\Models\PeriodoAcademico;
use Illuminate\Http\JsonResponse;

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
}
