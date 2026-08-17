<x-layout title="Configuración · CCTV Manager" active="configuracion">
    <div class="max-w-3xl">
        <x-ui.page-header
            title="Configuración"
            description="Actualiza tu cuenta, el IVA vigente y la identidad visual de la empresa."
        />

        @if (session('status'))
            <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error" class="mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <x-ui.card title="Datos de la cuenta">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.form-field label="Nombre" for="name" required class="sm:col-span-2">
                        <x-ui.input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                    <x-ui.form-field label="Correo" for="email" required class="sm:col-span-2">
                        <x-ui.input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                </div>
            </x-ui.card>

            <x-ui.card title="Cambiar contraseña">
                <p class="mb-4 text-sm text-foreground-muted">Déjalo vacío si no quieres cambiarla.</p>
                <div class="grid gap-4">
                    <x-ui.form-field label="Contraseña actual" for="current_password">
                        <x-ui.input
                            id="current_password"
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.form-field label="Nueva contraseña" for="password">
                            <x-ui.input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                class="min-h-11"
                            />
                        </x-ui.form-field>
                        <x-ui.form-field label="Confirmar nueva contraseña" for="password_confirmation">
                            <x-ui.input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                class="min-h-11"
                            />
                        </x-ui.form-field>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Identidad de la empresa">
                <p class="mb-4 text-sm text-foreground-muted">El logo aparece en la esquina superior derecha de las cotizaciones en PDF. Se almacena en Laravel Storage (disco público).</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.form-field label="Nombre comercial" for="company_name" class="sm:col-span-2">
                        <x-ui.input
                            id="company_name"
                            type="text"
                            name="company_name"
                            value="{{ old('company_name', $company['name'] ?? '') }}"
                            placeholder="Management CCTV"
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                    <x-ui.form-field label="NIT" for="company_nit">
                        <x-ui.input
                            id="company_nit"
                            type="text"
                            name="company_nit"
                            value="{{ old('company_nit', $company['nit'] ?? '') }}"
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                    <x-ui.form-field label="Teléfono" for="company_phone">
                        <x-ui.input
                            id="company_phone"
                            type="text"
                            name="company_phone"
                            value="{{ old('company_phone', $company['phone'] ?? '') }}"
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                    <x-ui.form-field label="Correo de la empresa" for="company_email" class="sm:col-span-2">
                        <x-ui.input
                            id="company_email"
                            type="email"
                            name="company_email"
                            value="{{ old('company_email', $company['email'] ?? '') }}"
                            class="min-h-11"
                        />
                    </x-ui.form-field>
                </div>

                @if (! empty($company['logo_url']))
                    <div class="mt-4 rounded-lg border border-border-subtle bg-background p-4">
                        <p class="mb-3 text-sm font-medium text-foreground">Logo actual</p>
                        <img
                            src="{{ $company['logo_url'] }}"
                            alt="Logo actual de la empresa"
                            class="max-h-20 w-auto max-w-[160px] object-contain"
                        >
                    </div>
                @endif

                <x-ui.form-field
                    label="Subir logo"
                    for="company_logo"
                    class="mt-4"
                    hint="JPEG, PNG o WebP. Máximo 2 MB. Reemplaza el logo existente."
                    :error="$errors->first('company_logo')"
                >
                    <input
                        id="company_logo"
                        type="file"
                        name="company_logo"
                        accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                        class="ui-input-base min-h-11 cursor-pointer py-2 file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-2 file:text-sm file:font-medium"
                    >
                </x-ui.form-field>

                @if (! empty($company['logo_path']))
                    <div class="mt-4">
                        <x-ui.checkbox
                            name="remove_company_logo"
                            value="1"
                            label="Eliminar logo actual"
                            description="Quita el logo de las cotizaciones hasta que se suba uno nuevo."
                        />
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="IVA configurable">
                <p class="mb-4 text-sm text-foreground-muted">Porcentaje vigente para nuevas cotizaciones en borrador. El valor aplicado se guarda históricamente en cada cotización.</p>
                <x-ui.form-field label="IVA (%)" for="vat_rate_percent" class="max-w-xs">
                    <x-ui.input
                        id="vat_rate_percent"
                        type="number"
                        step="0.0001"
                        min="0"
                        max="100"
                        name="vat_rate_percent"
                        value="{{ old('vat_rate_percent', $vatRatePercent) }}"
                        required
                        class="min-h-11"
                    />
                </x-ui.form-field>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="submit" class="min-h-11">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</x-layout>
