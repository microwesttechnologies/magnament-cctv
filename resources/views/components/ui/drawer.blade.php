@props(['side' => 'left', 'open' => 'false'])

<div
    x-show="{{ $open }}"
    x-cloak
    class="fixed inset-0 z-[60] lg:hidden"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-enter duration-medium motion-reduce:transition-none"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-exit duration-normal motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-primary/40"
        @click="{{ $open }} = false"
    ></div>
    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-enter duration-medium motion-reduce:transition-none"
        x-transition:enter-start="opacity-0 {{ $side === 'right' ? 'translate-x-full' : '-translate-x-full' }}"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-exit duration-normal motion-reduce:transition-none"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 {{ $side === 'right' ? 'translate-x-full' : '-translate-x-full' }}"
        {{ $attributes->merge(['class' => 'absolute inset-y-0 flex w-72 flex-col border-border bg-surface shadow-lg ' . ($side === 'right' ? 'right-0 border-l' : 'left-0 border-r')]) }}
    >
        {{ $slot }}
    </div>
</div>
