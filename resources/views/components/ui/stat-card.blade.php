@props([
    'label',
    'value',
    'delta' => null,
    'trend' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'min-w-0 rounded-lg border border-border bg-surface p-4 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-foreground-muted">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-foreground">{{ $value }}</p>
            @if ($delta !== null)
                <p @class([
                    'mt-1 text-xs font-medium',
                    'text-success' => $trend === 'up',
                    'text-destructive' => $trend === 'down',
                    'text-foreground-muted' => ! in_array($trend, ['up', 'down'], true),
                ])>{{ $delta }}</p>
            @endif
        </div>
        @if ($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent text-on-accent">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
