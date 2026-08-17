<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class TechnicianPwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $response = response()->json([
            'id' => '/tecnico',
            'name' => 'Management CCTV Técnicos',
            'short_name' => 'CCTV Técnicos',
            'description' => 'Órdenes de servicio para técnicos de campo',
            'start_url' => '/tecnico?source=pwa',
            'scope' => '/tecnico',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'lang' => 'es',
            'icons' => [
                [
                    'src' => '/images/pwa/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/images/pwa/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/images/pwa/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ]);

        $response->headers->set('Content-Type', 'application/manifest+json');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    public function serviceWorker(): BinaryFileResponse
    {
        return response()->file(public_path('pwa/tecnico-sw.js'), [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/tecnico',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function offline(): BinaryFileResponse
    {
        return response()->file(public_path('pwa/tecnico-offline.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
