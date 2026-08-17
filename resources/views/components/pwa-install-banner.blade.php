<div
    x-data="pwaInstallBanner()"
    class="pointer-events-none fixed inset-x-0 bottom-20 z-30 mx-auto max-w-lg px-4 motion-reduce:transition-none"
>
    <div
        x-show="visible"
        x-cloak
        x-transition.opacity.duration.150ms
        class="pointer-events-auto flex items-center justify-between gap-3 rounded-xl border border-border bg-surface p-3 shadow-lg"
    >
        <p class="text-sm font-medium">Instalar aplicación</p>
        <div class="flex gap-2">
            <x-ui.button size="sm" variant="ghost" type="button" @click="dismiss()">Ahora no</x-ui.button>
            <x-ui.button size="sm" type="button" @click="install()">Instalar</x-ui.button>
        </div>
    </div>
    <div
        x-show="iosHint && !visible"
        x-cloak
        class="pointer-events-auto mt-2 rounded-xl border border-border bg-surface p-3 text-sm shadow-lg"
    >
        <p class="font-medium">Instalar en iPhone</p>
        <p class="mt-1 text-foreground-muted">Comparte → <strong>Añadir a pantalla de inicio</strong>.</p>
        <x-ui.button size="sm" variant="ghost" type="button" class="mt-2" @click="dismiss()">Entendido</x-ui.button>
    </div>
</div>
