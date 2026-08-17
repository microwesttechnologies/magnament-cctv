@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1 text-foreground-muted">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1">
                @if ($index > 0)
                    <span class="text-border" aria-hidden="true">/</span>
                @endif
                @if (! empty($item['href']) && $index < count($items) - 1)
                    <a href="{{ $item['href'] }}" class="transition-colors hover:text-accent">{{ $item['label'] }}</a>
                @else
                    <span @class(['font-medium text-foreground' => $index === count($items) - 1]) aria-current="{{ $index === count($items) - 1 ? 'page' : false }}">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
