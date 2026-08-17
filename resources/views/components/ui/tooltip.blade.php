@props([
    'text',
    'position' => 'top',
])

@php
    $positionClass = match ($position) {
        'bottom' => 'top-full left-1/2 mt-2 -translate-x-1/2',
        'left' => 'right-full top-1/2 mr-2 -translate-y-1/2',
        'right' => 'left-full top-1/2 ml-2 -translate-y-1/2',
        default => 'bottom-full left-1/2 mb-2 -translate-x-1/2',
    };
@endphp

<span class="group relative inline-flex">
    {{ $slot }}
    <span
        role="tooltip"
        class="pointer-events-none absolute z-50 hidden whitespace-nowrap rounded-md bg-primary px-2 py-1 text-xs text-on-primary group-hover:block group-focus-within:block {{ $positionClass }}"
    >
        {{ $text }}
    </span>
</span>
