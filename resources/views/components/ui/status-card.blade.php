@props([
    'title',
    'status',
    'variant' => 'muted',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-border bg-surface p-4 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-foreground">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-xs text-foreground-muted">{{ $description }}</p>
            @endif
        </div>
        <x-ui.badge :variant="$variant" dot>{{ $status }}</x-ui.badge>
    </div>
    @isset($actions)
        <div class="mt-4 flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
