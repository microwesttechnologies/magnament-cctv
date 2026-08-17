@props(['title' => 'Algo salió mal', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-lg border border-destructive/30 bg-destructive-tint/30 px-6 py-10 text-center']) }} role="alert">
    <h3 class="text-base font-semibold text-destructive">{{ $title }}</h3>
    @if ($description)
        <p class="mt-2 text-sm text-foreground-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
