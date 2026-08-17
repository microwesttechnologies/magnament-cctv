<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use JsonException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TechnicianPwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $path = public_path('manifest.webmanifest');
        if (! is_file($path)) {
            Log::error('[TechnicianPwaController] missing PWA asset', ['path' => 'manifest.webmanifest']);
            throw new NotFoundHttpException('Recurso PWA no encontrado.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::error('[TechnicianPwaController] invalid PWA manifest', ['error' => $exception->getMessage()]);
            throw new NotFoundHttpException('Recurso PWA no encontrado.');
        }

        $response = response()->json($payload);
        $response->headers->set('Content-Type', 'application/manifest+json');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    public function serviceWorker(): BinaryFileResponse
    {
        return $this->publicFile('sw.js', [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function offline(): BinaryFileResponse
    {
        return $this->publicFile('offline.html', [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function publicFile(string $relative, array $headers): BinaryFileResponse
    {
        $path = public_path($relative);
        if (! is_file($path)) {
            Log::error('[TechnicianPwaController] missing PWA asset', ['path' => $relative]);
            throw new NotFoundHttpException('Recurso PWA no encontrado.');
        }

        return response()->file($path, $headers);
    }
}
