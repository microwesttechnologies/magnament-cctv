@props([
    'variant' => 'default',
    'dot' => false,
])

@php
    $classes = match ($variant) {
        'info' => 'bg-info-tint text-accent',
        'success' => 'bg-success-tint text-green-800 dark:text-green-300',
        'warning' => 'bg-warning-tint text-amber-800 dark:text-amber-300',
        'error', 'destructive' => 'bg-destructive-tint text-red-800 dark:text-red-300',
        'accent' => 'bg-muted-tint text-accent',
        'muted' => 'bg-muted text-foreground-muted',
        default => 'bg-muted text-foreground-muted',
    };
@endphp

<span {{ $attributes->merge(['class' => "ui-badge {$classes}"]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
