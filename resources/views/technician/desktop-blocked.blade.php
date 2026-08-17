<x-layout.technician title="Acceso móvil · Management CCTV" active="home">
    <div class="rounded-xl border border-border bg-surface p-6 text-center">
        <h1 class="text-xl font-bold">Aplicación para móviles</h1>
        <p class="mt-3 text-sm text-foreground-muted">Esta aplicación está diseñada para técnicos desde dispositivos móviles.</p>
        <p class="mt-3 text-sm">Abre en el celular: <a class="font-medium text-accent underline" href="{{ route('technician.login') }}">{{ url('/tecnico/login') }}</a></p>
        <p class="mt-2 text-xs text-foreground-muted">Si ya la instalaste, ábrela desde el icono de la pantalla de inicio.</p>
        <form method="POST" action="{{ route('technician.logout') }}" class="mt-6">
            @csrf
            <x-ui.button type="submit" variant="outline" class="min-h-11 w-full justify-center">Cerrar sesión</x-ui.button>
        </form>
    </div>
</x-layout.technician>
