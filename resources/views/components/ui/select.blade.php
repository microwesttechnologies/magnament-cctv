@props(['error' => false])

<select {{ $attributes->merge(['class' => 'ui-input-base' . ($error ? ' ui-input-error' : '')]) }}>
    {{ $slot }}
</select>
