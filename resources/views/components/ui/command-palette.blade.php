@props([
    'open' => 'commandOpen',
])

<div
    x-show="{{ $open }}"
    x-cloak
    class="fixed inset-0 z-[80] flex items-start justify-center bg-primary/40 p-4 pt-[15vh]"
    role="dialog"
    aria-modal="true"
    aria-label="Paleta de comandos"
    @keydown.escape.window="{{ $open }} = false"
>
    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-out duration-180 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0 scale-[0.98]"
        x-transition:enter-end="opacity-100 scale-100"
        class="w-full max-w-xl overflow-hidden rounded-lg border border-border bg-surface shadow-lg"
        @click.outside="{{ $open }} = false"
    >
        <div class="border-b border-border-subtle px-4 py-3">
            <input
                type="search"
                placeholder="Buscar acciones…"
                class="ui-input-base w-full border-0 bg-transparent px-0 shadow-none focus:ring-0"
                autofocus
            >
        </div>
        <div class="max-h-80 overflow-y-auto p-2">
            {{ $slot }}
        </div>
    </div>
</div>
