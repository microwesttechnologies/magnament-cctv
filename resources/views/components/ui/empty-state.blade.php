@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'motion-empty flex flex-col items-center justify-center rounded-lg border border-dashed border-border bg-surface px-6 py-12 text-center']) }}>
    @isset($icon)
        <div class="mb-4 text-foreground-muted">{{ $icon }}</div>
    @endisset
    <h3 class="text-base font-semibold text-foreground">{{ $title }}</h3>
    @if ($description)
        <p class="mt-2 max-w-md text-sm text-foreground-muted">{{ $description }}</p>
    @endif
    @if ($actionLabel && $actionHref)
        <x-ui.button :href="$actionHref" class="mt-6">{{ $actionLabel }}</x-ui.button>
    @endif
    {{ $slot }}
</div>
