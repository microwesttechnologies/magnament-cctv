<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Application\ServiceOrder\TechnicianAccountProvisioner;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

final class TechnicianAuthController extends Controller
{
    public function show(): View
    {
        return view('technician.login');
    }

    public function store(Request $request, TechnicianAccountProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'document_number' => ['required', 'string', 'max:32'],
        ]);

        $staff = Staff::query()
            ->where('email', $validated['email'])
            ->where('document_number', $validated['document_number'])
            ->where('role', 'tecnico')
            ->where('status', 'activo')
            ->first();

        if ($staff === null) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'No encontramos un técnico activo con ese correo y cédula.']);
        }

        try {
            $user = $provisioner->ensureUser($staff);
        } catch (InvalidArgumentException $e) {
            return back()->withInput($request->only('email'))->withErrors(['email' => $e->getMessage()]);
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
