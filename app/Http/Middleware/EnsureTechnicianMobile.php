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
        if ($this->isMobile($request)) {
            return $next($request);
        }

        return response()->view('technician.desktop-blocked', [
            'user' => $request->user(),
        ], 403);
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
