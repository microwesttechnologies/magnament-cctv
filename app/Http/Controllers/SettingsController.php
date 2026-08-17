<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Quotation\Ports\VatSettingsInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function edit(VatSettingsInterface $vatSettings): View
    {
        $vatRate = null;
        try {
            $vatRate = $vatSettings->currentVatRatePercent();
        } catch (\Throwable) {
            $vatRate = '';
        }

        return view('settings.edit', [
            'user' => Auth::user(),
            'vatRatePercent' => $vatRate,
        ]);
    }

    public function update(Request $request, VatSettingsInterface $vatSettings): RedirectResponse
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
            'vat_rate_percent' => ['required', 'numeric', 'gte:0', 'lte:100'],
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

        $vatSettings->updateVatRatePercent((string) $validated['vat_rate_percent']);
        Log::info('[SettingsController.update] profile and VAT updated', [
            'user_id' => $user->id,
            'vat_rate_percent' => $validated['vat_rate_percent'],
        ]);

        return redirect()
            ->route('configuracion')
            ->with('status', 'Configuración actualizada correctamente.');
    }
}
