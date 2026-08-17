<div>
    <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Contacto del proyecto</h3>
    <div class="mt-3 grid gap-4">
        <x-ui.form-field label="Nombre del administrador" for="{{ $prefix ?? '' }}admin_name" required>
            <x-ui.input
                id="{{ $prefix ?? '' }}admin_name"
                type="text"
                name="admin_name"
                value="{{ old('admin_name', $adminName ?? '') }}"
                required
                class="min-h-11 w-full"
            />
        </x-ui.form-field>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.form-field label="Teléfono" for="{{ $prefix ?? '' }}admin_phone">
                <x-ui.input
                    id="{{ $prefix ?? '' }}admin_phone"
                    type="text"
                    name="admin_phone"
                    value="{{ old('admin_phone', $adminPhone ?? '') }}"
                    class="min-h-11 w-full"
                />
            </x-ui.form-field>
            <x-ui.form-field label="Correo electrónico" for="{{ $prefix ?? '' }}admin_email">
                <x-ui.input
                    id="{{ $prefix ?? '' }}admin_email"
                    type="email"
                    name="admin_email"
                    value="{{ old('admin_email', $adminEmail ?? '') }}"
                    class="min-h-11 w-full"
                />
            </x-ui.form-field>
        </div>
    </div>
</div>
