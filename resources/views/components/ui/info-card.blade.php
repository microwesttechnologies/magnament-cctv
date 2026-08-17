@props([
    'title' => null,
    'variant' => 'default',
])

@php
    $variantClass = match ($variant) {
        'info' => 'border-info/20 bg-info/5',
        'success' => 'border-success/20 bg-success/5',
        'warning' => 'border-warning/20 bg-warning/5',
        'error' => 'border-destructive/20 bg-destructive/5',
        default => 'border-border bg-surface',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 shadow-sm {$variantClass}"]) }}>
    @if ($title)
        <h3 class="text-sm font-semibold text-foreground">{{ $title }}</h3>
    @endif
    <div @class(['text-sm text-foreground-muted', 'mt-2' => (bool) $title])>{{ $slot }}</div>
</div>
