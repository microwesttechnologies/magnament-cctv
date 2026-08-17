@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'ui-table-wrap']) }}>
    @if ($title)
        <div class="border-b border-border px-4 py-3">
            <h3 class="text-sm font-semibold text-foreground">{{ $title }}</h3>
        </div>
    @endif
    <table class="ui-table">
        {{ $slot }}
    </table>
</div>
