<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOfficeUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->isTechnician()) {
            return redirect()->route('technician.home');
        }

        return $next($request);
    }
}
