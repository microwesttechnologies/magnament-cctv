<x-layout.technician title="Perfil · Management CCTV" active="profile">
    <h1 class="text-2xl font-bold">Perfil</h1>
    <div class="mt-4 rounded-xl border border-border bg-surface p-4">
        <p class="text-lg font-semibold">{{ $user->name }}</p>
        <p class="mt-1 text-sm text-foreground-muted">{{ $user->email }}</p>
        <p class="mt-3 text-sm">Rol: técnico de campo</p>
        @if ($user->staff)
            <p class="mt-1 text-sm text-foreground-muted">Cédula registrada en personal (no se muestra completa aquí por privacidad).</p>
        @endif
    </div>

    <section class="mt-4 rounded-xl border border-border bg-surface p-4" x-data="pushPermission()">
        <h2 class="text-base font-semibold">Notificaciones</h2>
        <p class="mt-1 text-sm text-foreground-muted">Actívalas para enterarte de asignaciones y reasignaciones. No volveremos a pedirlo si lo rechazas.</p>
        <p class="mt-3 text-sm" x-text="statusLabel" x-cloak></p>
        <x-ui.button type="button" class="mt-3 min-h-12 w-full justify-center" @click="enable()" x-show="canEnable">Activar notificaciones</x-ui.button>
    </section>

    <form method="POST" action="{{ route('technician.logout') }}" class="mt-6">
        @csrf
        <x-ui.button type="submit" variant="outline" class="min-h-12 w-full justify-center">Cerrar sesión</x-ui.button>
    </form>
</x-layout.technician>
