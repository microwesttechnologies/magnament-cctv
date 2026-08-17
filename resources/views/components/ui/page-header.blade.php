@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @isset($breadcrumbs)
            <div class="mb-2">{{ $breadcrumbs }}</div>
        @endisset
        <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-foreground-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
