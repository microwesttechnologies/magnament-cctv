@props(['title' => null])

<x-ui.card :title="$title" {{ $attributes }}>
    <div class="min-h-[12rem]">
        {{ $slot }}
    </div>
</x-ui.card>
