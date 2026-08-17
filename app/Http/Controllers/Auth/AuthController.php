<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\ServiceOrder\TechnicianCredentialAuthenticator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * La autenticación es una responsabilidad del framework (sesiones, hashing),
 * por eso se resuelve con el sistema de Auth de Laravel y no en el dominio.
 */
final class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, TechnicianCredentialAuthenticator $authenticator): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()?->isTechnician()) {
                return redirect()->route('technician.home');
            }

            return redirect()->route('dashboard');
        }

        try {
            $user = $authenticator->authenticate($credentials['email'], $credentials['password']);
        } catch (InvalidArgumentException) {
            Log::warning('[FIX] Office login failed', [
                'email' => mb_strtolower(trim($credentials['email'])),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Las credenciales no coinciden con nuestros registros.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        Log::info('[FIX] Technician logged in from office form using cédula', [
            'user_id' => $user->id,
        ]);

        return redirect()->route('technician.home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
