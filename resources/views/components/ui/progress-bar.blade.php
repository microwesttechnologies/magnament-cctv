@props([
    'value' => 0,
    'max' => 100,
    'variant' => 'accent',
    'size' => 'md',
    'label' => null,
    'showLabel' => true,
])

@php
    $percent = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;
    $barColor = match ($variant) {
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'destructive' => 'bg-destructive',
        default => 'bg-accent',
    };
    $height = $size === 'lg' ? 'h-2.5' : 'h-1.5';
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($showLabel && $label)
        <div class="mb-1 flex items-center justify-between text-xs text-foreground-muted">
            <span>{{ $label }}</span>
            <span>{{ round($percent) }}%</span>
        </div>
    @endif
    <div class="{{ $height }} w-full overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}">
        <div class="{{ $height }} rounded-full transition-all duration-200 motion-reduce:transition-none {{ $barColor }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
