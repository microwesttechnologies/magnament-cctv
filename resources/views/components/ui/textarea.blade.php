@props(['error' => false, 'rows' => 3])

<textarea
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'ui-input-base min-h-[5rem] py-2' . ($error ? ' ui-input-error' : '')]) }}
>{{ $slot }}</textarea>
