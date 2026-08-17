@props([
    'current' => 1,
    'total' => 1,
    'perPage' => 15,
])

@if ($total > 1)
    <nav aria-label="Paginación" {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 border-t border-border px-4 py-3']) }}>
        <p class="text-sm text-foreground-muted">Página {{ $current }} de {{ $total }}</p>
        <div class="flex gap-1">
            @if ($current > 1)
                <x-ui.button variant="secondary" size="sm" href="{{ request()->fullUrlWithQuery(['page' => $current - 1]) }}">Anterior</x-ui.button>
            @endif
            @if ($current < $total)
                <x-ui.button variant="secondary" size="sm" href="{{ request()->fullUrlWithQuery(['page' => $current + 1]) }}">Siguiente</x-ui.button>
            @endif
        </div>
    </nav>
@endif
