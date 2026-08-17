@props([
    'open' => false,
    'title' => '',
    'maxWidth' => 'lg',
])

@php
    $widthClass = match ($maxWidth) {
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full',
        default => 'max-w-2xl',
    };
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    class="fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
    @if($title) aria-labelledby="modal-title" @endif
>
    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-out duration-180 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-120 motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-primary/40"
        @click="{{ $attributes->get('@close', 'open = false') }}"
    ></div>
    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-out duration-180 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0 scale-[0.98]"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-120 motion-reduce:transition-none"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-[0.98]"
        {{ $attributes->merge(['class' => "relative w-full {$widthClass} rounded-lg border border-border bg-surface shadow-lg"]) }}
    >
        @if ($title)
            <div class="border-b border-border-subtle px-6 py-4">
                <h2 id="modal-title" class="text-lg font-semibold text-foreground">{{ $title }}</h2>
            </div>
        @endif
        <div class="px-6 py-4">{{ $slot }}</div>
        @isset($footer)
            <div class="flex justify-end gap-2 border-t border-border-subtle px-6 py-4">{{ $footer }}</div>
        @endisset
    </div>
</div>
