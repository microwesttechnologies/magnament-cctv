@props([
    'label',
    'value',
    'unit' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-border bg-surface p-6 shadow-sm']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-foreground-muted">{{ $label }}</p>
    <p class="mt-2 font-mono text-3xl font-bold tracking-tight text-foreground">
        {{ $value }}@if($unit)<span class="ml-1 text-lg font-medium text-foreground-muted">{{ $unit }}</span>@endif
    </p>
    @if ($hint)
        <p class="mt-2 text-xs text-foreground-muted">{{ $hint }}</p>
    @endif
</div>
