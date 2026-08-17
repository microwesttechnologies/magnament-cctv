@props([
    'title' => null,
    'padding' => true,
    'interactive' => false,
])

<div {{ $attributes->merge(['class' => 'ui-card min-w-0' . ($padding ? '' : ' p-0') . ($interactive ? ' ui-card-interactive' : '')]) }}>
    @if ($title || isset($header))
        <div class="mb-4 flex items-center justify-between gap-4 border-b border-border-subtle pb-4">
            @if ($title)
                <h2 class="text-base font-semibold text-foreground">{{ $title }}</h2>
            @endif
            @isset($header)
                {{ $header }}
            @endisset
        </div>
    @endif
    {{ $slot }}
    @isset($footer)
        <div class="mt-4 border-t border-border-subtle pt-4">
            {{ $footer }}
        </div>
    @endisset
</div>
