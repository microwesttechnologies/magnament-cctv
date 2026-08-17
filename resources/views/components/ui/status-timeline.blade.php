@props(['events' => []])

<ul {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @forelse ($events as $event)
        <li class="relative pl-6">
            <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full bg-accent" aria-hidden="true"></span>
            <span class="absolute bottom-0 left-[4px] top-4 w-px bg-border" aria-hidden="true"></span>
            <p class="text-sm font-medium text-foreground">{{ $event->title ?? $event['title'] ?? '' }}</p>
            <p class="mt-0.5 text-xs text-foreground-muted">
                {{ $event->created_at?->diffForHumans() ?? ($event['time'] ?? '') }}
            </p>
        </li>
    @empty
        <li class="text-sm text-foreground-muted">Sin eventos registrados.</li>
    @endforelse
</ul>
