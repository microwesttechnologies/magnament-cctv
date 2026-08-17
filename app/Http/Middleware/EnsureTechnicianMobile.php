<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTechnicianMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isMobile($request) || $this->isInstalledPwa($request)) {
            /** @var Response $response */
            $response = $next($request);
            if ($request->query('source') === 'pwa' && $request->cookies->get('pwa_standalone') !== '1') {
                $response->headers->setCookie(cookie(
                    'pwa_standalone',
                    '1',
                    60 * 24 * 365,
                    '/',
                    null,
                    $request->isSecure(),
                    false,
                    false,
                    'lax',
                ));
            }

            return $response;
        }

        return response()->view('technician.desktop-blocked', [
            'user' => $request->user(),
        ], 403);
    }

    private function isInstalledPwa(Request $request): bool
    {
        return $request->query('source') === 'pwa'
            || $request->cookies->get('pwa_standalone') === '1';
    }

    private function isMobile(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());
        if ($agent === '') {
            return false;
        }

        return (bool) preg_match('/mobile|android|iphone|ipad|ipod|webos|blackberry|iemobile|opera mini/i', $agent);
    }
}
