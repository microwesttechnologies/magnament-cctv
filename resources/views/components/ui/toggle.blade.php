@props([
    'label' => null,
    'description' => null,
    'checked' => false,
])

<label {{ $attributes->merge(['class' => 'flex min-h-11 cursor-pointer items-center justify-between gap-4']) }}>
    @if ($label || $description)
        <span class="min-w-0">
            @if ($label)
                <span class="block text-sm font-medium text-foreground">{{ $label }}</span>
            @endif
            @if ($description)
                <span class="mt-0.5 block text-xs text-foreground-muted">{{ $description }}</span>
            @endif
        </span>
    @endif
    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
        <input
            type="checkbox"
            role="switch"
            {{ $attributes->except('class') }}
            @checked($checked)
            class="peer sr-only"
        >
        <span
            class="absolute inset-0 rounded-full bg-border transition-colors duration-150 peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40 motion-reduce:transition-none"
            aria-hidden="true"
        ></span>
        <span
            class="absolute left-0.5 h-5 w-5 rounded-full bg-surface shadow-sm transition-transform duration-150 peer-checked:translate-x-5 motion-reduce:transition-none"
            aria-hidden="true"
        ></span>
    </span>
</label>
