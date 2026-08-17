@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
    'href' => null,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'ui-btn-secondary',
        'outline' => 'ui-btn-outline',
        'ghost' => 'ui-btn-ghost',
        'destructive' => 'ui-btn-destructive',
        'success' => 'ui-btn-success',
        default => 'ui-btn-primary',
    };
    $sizeClass = match ($size) {
        'sm' => 'ui-btn-sm',
        'lg' => 'ui-btn-lg',
        default => 'ui-btn-md',
    };
    $classes = trim("ui-btn-base {$variantClass} {$sizeClass} {$attributes->get('class')}");
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($disabled) aria-disabled="true" tabindex="-1" @endif>
        @if ($loading)
            <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @disabled($disabled || $loading)
        @if($loading) aria-busy="true" @endif
    >
        @if ($loading)
            <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
