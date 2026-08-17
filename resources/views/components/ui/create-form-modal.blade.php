@props([
    'open' => 'createOpen',
    'title' => '',
    'description' => '',
    'maxWidth' => 'xl',
    'formId' => null,
])

@php
    $widthClass = match ($maxWidth) {
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'full' => 'max-w-[min(100%,56rem)] sm:max-h-[92vh]',
        default => 'max-w-3xl',
    };
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    class="fixed inset-0 z-[75] flex items-end justify-center p-0 sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="create-modal-title" @endif
    @keydown.escape.window="requestClose()"
>
    <div
        x-show="{{ $open }}"
        x-transition.opacity
        class="absolute inset-0 bg-primary/40"
        @click="requestClose()"
    ></div>
    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0 scale-[0.98] translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        class="relative flex max-h-[100dvh] w-full flex-col {{ $widthClass }} rounded-none border border-border bg-surface shadow-lg sm:max-h-[90vh] sm:rounded-xl"
        @click.stop
    >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-border-subtle px-5 py-4 sm:px-6">
            <div class="min-w-0">
                @if ($title)
                    <h2 id="create-modal-title" class="text-lg font-semibold text-foreground">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-foreground-muted">{{ $description }}</p>
                @endif
            </div>
            <button type="button" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-foreground-muted transition hover:bg-muted hover:text-foreground" @click="requestClose()" aria-label="Cerrar">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 sm:px-6">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-border-subtle px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                {{ $footer }}
            </div>
        @endisset
    </div>

    <div x-show="discardOpen" x-cloak class="absolute inset-0 z-[80] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-primary/50" @click="discardOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl border border-border bg-surface p-5 shadow-lg">
            <h3 class="text-base font-semibold text-foreground">¿Deseas descartar los cambios?</h3>
            <p class="mt-1 text-sm text-foreground-muted">Perderás la información que hayas introducido.</p>
            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button type="button" variant="outline" class="min-h-11" @click="discardOpen = false">Continuar editando</x-ui.button>
                <x-ui.button type="button" variant="destructive" class="min-h-11" @click="confirmDiscard()">Descartar</x-ui.button>
            </div>
        </div>
    </div>
</div>
