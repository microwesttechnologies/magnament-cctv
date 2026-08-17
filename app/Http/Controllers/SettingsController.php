<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users_tb', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'current_password.required_with' => 'Ingresa tu contraseña actual para cambiarla.',
        ]);

        if (! empty($validated['password'])) {
            if (! Hash::check((string) $request->input('current_password'), $user->password)) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                    ->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
            }

            $user->password = $validated['password'];
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return redirect()
            ->route('configuracion')
            ->with('status', 'Perfil actualizado correctamente.');
    }
}
