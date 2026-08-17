<?php

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'office' => \App\Http\Middleware\EnsureOfficeUser::class,
            'technician' => \App\Http\Middleware\EnsureTechnician::class,
            'technician.mobile' => \App\Http\Middleware\EnsureTechnicianMobile::class,
        ]);
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();
            if ($user instanceof User && $user->isTechnician()) {
                return route('technician.home');
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
