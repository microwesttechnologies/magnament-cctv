<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Application\ServiceOrder\TechnicianCredentialAuthenticator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;

final class TechnicianAuthController extends Controller
{
    public function show(): View
    {
        return view('technician.login');
    }

    public function store(Request $request, TechnicianCredentialAuthenticator $authenticator): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'document_number' => ['required', 'string', 'max:50'],
        ]);

        try {
            $user = $authenticator->authenticate($validated['email'], $validated['document_number']);
        } catch (InvalidArgumentException $e) {
            Log::warning('[FIX] Technician login rejected', [
                'email' => mb_strtolower(trim($validated['email'])),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('technician.home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('technician.login');
    }
}
