<?php

namespace App\Modules\Personas\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente para APIsPERU (consulta de identidad DNI / RUC).
 * El token vive en config('services.apisperu.token') -> .env, nunca en el frontend.
 */
class ConsultaService
{
    private string $baseUrl;
    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.apisperu.base_url'), '/');
        $this->token   = config('services.apisperu.token');
    }

    /**
     * Consulta un DNI (8 dígitos). Devuelve nombres y apellidos normalizados.
     *
     * @return array{dni:string, nombres:string, apellido_paterno:string, apellido_materno:string, apellidos:string, nombre_completo:string}
     */
    public function dni(string $dni): array
    {
        $data = $this->get("dni/{$dni}", "apisperu:dni:{$dni}");

        $nombres  = trim((string) ($data['nombres'] ?? ''));
        $paterno  = trim((string) ($data['apellidoPaterno'] ?? ''));
        $materno  = trim((string) ($data['apellidoMaterno'] ?? ''));
        $apellidos = trim("{$paterno} {$materno}");

        return [
            'dni'              => (string) ($data['dni'] ?? $dni),
            'nombres'          => $nombres,
            'apellido_paterno' => $paterno,
            'apellido_materno' => $materno,
            'apellidos'        => $apellidos,
            'nombre_completo'  => trim("{$nombres} {$apellidos}"),
        ];
    }

    /**
     * Consulta un RUC (11 dígitos). Devuelve razón social y datos del contribuyente.
     */
    public function ruc(string $ruc): array
    {
        $data = $this->get("ruc/{$ruc}", "apisperu:ruc:{$ruc}");

        return [
            'ruc'            => (string) ($data['ruc'] ?? $ruc),
            'razon_social'   => trim((string) ($data['razonSocial'] ?? '')),
            'nombre_comercial' => trim((string) ($data['nombreComercial'] ?? '')),
            'direccion'      => trim((string) ($data['direccion'] ?? '')),
            'distrito'       => trim((string) ($data['distrito'] ?? '')),
            'provincia'      => trim((string) ($data['provincia'] ?? '')),
            'departamento'   => trim((string) ($data['departamento'] ?? '')),
            'estado'         => trim((string) ($data['estado'] ?? '')),
            'condicion'      => trim((string) ($data['condicion'] ?? '')),
        ];
    }

    /**
     * GET genérico con cache (1 día) y manejo de errores de APIsPERU.
     * Lanza RuntimeException con código HTTP apropiado en el mensaje vía data.
     *
     * @return array<string,mixed>
     */
    private function get(string $path, string $cacheKey): array
    {
        if (blank($this->token)) {
            throw new RuntimeException('APISPERU_TOKEN no configurado en el servidor.', 500);
        }

        return Cache::remember($cacheKey, now()->addDay(), function () use ($path) {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get("{$this->baseUrl}/{$path}", ['token' => $this->token]);

            if ($response->failed()) {
                // 422 token inválido/agotado, 404, etc. -> propagamos como servicio no disponible
                throw new RuntimeException('No se pudo consultar el servicio de identidad.', 502);
            }

            $body = $response->json();

            // APIsPERU responde { success:false, message:"No se encontraron resultados." }
            if (! is_array($body) || ($body['success'] ?? false) !== true) {
                throw new RuntimeException(
                    (string) ($body['message'] ?? 'No se encontraron resultados.'),
                    404
                );
            }

            return $body;
        });
    }
}
