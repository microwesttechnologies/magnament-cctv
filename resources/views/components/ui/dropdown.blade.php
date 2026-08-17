@props([
    'align' => 'right',
])

@php
    $alignClass = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<div
    x-data="{ open: false }"
    class="relative inline-block text-left"
    @keydown.escape.window="open = false"
    {{ $attributes->except('class') }}
>
    <div @click="open = !open">
        {{ $trigger }}
    </div>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-120 motion-reduce:transition-none"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="open = false"
        class="absolute z-50 mt-2 min-w-48 rounded-lg border border-border bg-surface py-1 shadow-lg {{ $alignClass }}"
        role="menu"
    >
        {{ $menu }}
    </div>
</div>
