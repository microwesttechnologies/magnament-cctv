<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTechnician
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('technician.login');
        }

        if (! $user->isTechnician()) {
            abort(403, 'Esta área es exclusiva para técnicos.');
        }

        return $next($request);
    }
}
