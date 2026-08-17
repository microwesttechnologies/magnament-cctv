@props([
    'error' => false,
    'success' => false,
])

@php
    $extra = '';
    if ($error) {
        $extra = 'ui-input-error';
    } elseif ($success) {
        $extra = 'border-success';
    }
@endphp

<input {{ $attributes->merge(['class' => "ui-input-base {$extra}"]) }} />
