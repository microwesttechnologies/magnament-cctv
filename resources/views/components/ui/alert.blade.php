@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $variantClasses = match ($variant) {
        'success' => 'border-l-success bg-success-tint/50 text-foreground',
        'warning' => 'border-l-warning bg-warning-tint/50 text-foreground',
        'error' => 'border-l-destructive bg-destructive-tint/50 text-foreground',
        default => 'border-l-info bg-info-tint/50 text-foreground',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex gap-3 rounded-lg border border-border border-l-4 p-4 {$variantClasses}", 'role' => 'alert']) }}>
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-sm font-semibold">{{ $title }}</p>
        @endif
        <div @class(['text-sm', 'mt-1' => (bool) $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="shrink-0 text-foreground-muted hover:text-foreground ui-focus" aria-label="Cerrar">&times;</button>
    @endif
</div>
