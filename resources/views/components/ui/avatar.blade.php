@props([
    'name' => '',
    'src' => null,
    'size' => 'md',
])

@php
    $initial = strtoupper(substr($name ?: 'U', 0, 1));
    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-11 w-11 text-base',
        default => 'h-9 w-9 text-sm',
    };
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->merge(['class' => "rounded-full object-cover {$sizeClass}"]) }} />
@else
    <div {{ $attributes->merge(['class' => "flex items-center justify-center rounded-full bg-primary font-semibold text-on-primary {$sizeClass}"]) }} aria-hidden="true">
        {{ $initial }}
    </div>
@endif
