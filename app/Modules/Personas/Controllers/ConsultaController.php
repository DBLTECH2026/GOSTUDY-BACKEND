<?php

namespace App\Modules\Personas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personas\Services\ConsultaService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Proxy seguro hacia APIsPERU. El token nunca llega al navegador:
 * el frontend pega aquí (autenticado con Sanctum) y este controlador consulta.
 */
class ConsultaController extends Controller
{
    public function __construct(private ConsultaService $consulta)
    {
    }

    public function dni(string $dni): JsonResponse
    {
        if (! preg_match('/^\d{8}$/', $dni)) {
            return response()->json(['message' => 'El DNI debe tener 8 dígitos.'], 422);
        }

        return $this->responder(fn () => $this->consulta->dni($dni));
    }

    public function ruc(string $ruc): JsonResponse
    {
        if (! preg_match('/^\d{11}$/', $ruc)) {
            return response()->json(['message' => 'El RUC debe tener 11 dígitos.'], 422);
        }

        return $this->responder(fn () => $this->consulta->ruc($ruc));
    }

    /**
     * @param  callable():array<string,mixed>  $fn
     */
    private function responder(callable $fn): JsonResponse
    {
        try {
            return response()->json(['data' => $fn()]);
        } catch (RuntimeException $e) {
            $status = (int) $e->getCode();
            $status = ($status >= 400 && $status < 600) ? $status : 502;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
