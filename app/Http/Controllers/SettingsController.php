<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Infrastructure\Settings\EloquentCompanyIdentity;
use App\Rules\ValidRasterImage;
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
    public function edit(VatSettingsInterface $vatSettings, EloquentCompanyIdentity $companyIdentity): View
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
            'company' => $companyIdentity->snapshot(),
        ]);
    }

    public function update(
        Request $request,
        VatSettingsInterface $vatSettings,
        EloquentCompanyIdentity $companyIdentity,
    ): RedirectResponse {
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_nit' => ['nullable', 'string', 'max:64'],
            'company_phone' => ['nullable', 'string', 'max:64'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_logo' => ['nullable', 'bail', new ValidRasterImage, 'max:2048'],
            'remove_company_logo' => ['nullable', 'boolean'],
        ], [
            'current_password.required_with' => 'Ingresa tu contraseña actual para cambiarla.',
            'company_logo.file' => 'El logo debe ser un archivo de imagen.',
            'company_logo.max' => 'El logo no puede superar 2 MB.',
        ]);

        if (! empty($validated['password'])) {
            if (! Hash::check((string) $request->input('current_password'), $user->password)) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'current_password', 'company_logo']))
                    ->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
            }

            $user->password = $validated['password'];
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        $vatSettings->updateVatRatePercent((string) $validated['vat_rate_percent']);

        $logo = $request->file('company_logo');
        $removeLogo = $request->boolean('remove_company_logo') && $logo === null;
        $companyIdentity->update([
            'name' => (string) ($validated['company_name'] ?? ''),
            'nit' => (string) ($validated['company_nit'] ?? ''),
            'phone' => (string) ($validated['company_phone'] ?? ''),
            'email' => (string) ($validated['company_email'] ?? ''),
        ], $logo, $removeLogo);

        Log::info('[SettingsController.update] profile, VAT and company identity updated', [
            'user_id' => $user->id,
            'vat_rate_percent' => $validated['vat_rate_percent'],
            'logo_replaced' => $logo !== null,
            'logo_removed' => $removeLogo,
        ]);

        return redirect()
            ->route('configuracion')
            ->with('status', 'Configuración actualizada correctamente.');
    }
}
