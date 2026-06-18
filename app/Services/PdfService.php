<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PdfService
{
    public function configurado(): bool
    {
        return !empty(config('services.browserless.token'));
    }

    public function fromUrl(string $url): string
    {
        $base = rtrim((string) config('services.browserless.url'), '/');
        $token = config('services.browserless.token');
        $resp = Http::timeout(60)->post("{$base}/pdf?token={$token}", [
            'url' => $url,
            'options' => ['format' => 'A4', 'printBackground' => true, 'margin' => ['top' => '10mm', 'bottom' => '10mm', 'left' => '8mm', 'right' => '8mm']],
            'gotoOptions' => ['waitUntil' => 'networkidle2'],
        ]);
        abort_unless($resp->successful(), 502, 'Browserless error: ' . $resp->status());
        return $resp->body();
    }
}
