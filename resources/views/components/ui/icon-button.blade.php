@props([
    'variant' => 'ghost',
    'size' => 'md',
    'type' => 'button',
    'label' => '',
])

@php
    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8',
        'lg' => 'h-11 w-11',
        default => 'h-10 w-10',
    };
    $variantClass = match ($variant) {
        'primary' => 'text-on-accent bg-accent hover:brightness-95',
        'destructive' => 'text-destructive hover:bg-destructive-tint',
        default => 'text-foreground-muted hover:bg-muted hover:text-foreground',
    };
@endphp

<button
    type="{{ $type }}"
    aria-label="{{ $label }}"
    title="{{ $label }}"
    {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-md transition-colors duration-150 ui-focus {$sizeClass} {$variantClass}"]) }}
>
    {{ $slot }}
</button>
