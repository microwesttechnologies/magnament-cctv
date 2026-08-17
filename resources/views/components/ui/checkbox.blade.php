@props([
    'label' => null,
    'description' => null,
])

<label {{ $attributes->merge(['class' => 'flex min-h-11 cursor-pointer items-start gap-3']) }}>
    <input
        type="checkbox"
        {{ $attributes->except('class') }}
        class="ui-input-base mt-0.5 h-5 w-5 shrink-0 rounded border-border text-accent focus:ring-accent"
    >
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
</label>
