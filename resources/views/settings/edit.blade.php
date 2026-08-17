<x-layout title="Configuración · CCTV Manager" active="configuracion">
    <div class="max-w-2xl">
        <x-ui.page-header
            title="Configuración"
            description="Actualiza tu nombre, correo y contraseña de acceso."
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

        <form method="POST" action="{{ route('configuracion.update') }}" class="space-y-6">
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
                        />
                    </x-ui.form-field>
                    <x-ui.form-field label="Correo" for="email" required class="sm:col-span-2">
                        <x-ui.input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            required
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
                        />
                    </x-ui.form-field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.form-field label="Nueva contraseña" for="password">
                            <x-ui.input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                            />
                        </x-ui.form-field>
                        <x-ui.form-field label="Confirmar nueva contraseña" for="password_confirmation">
                            <x-ui.input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                            />
                        </x-ui.form-field>
                    </div>
                </div>
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
                    />
                </x-ui.form-field>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="submit">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</x-layout>
