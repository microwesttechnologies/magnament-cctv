@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'for' => null,
])

@php
    $inputId = $for ?? $attributes->get('id');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if($inputId) for="{{ $inputId }}" @endif class="block text-sm font-medium text-foreground">
            {{ $label }}
            @if ($required)<span class="text-accent" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="text-xs text-foreground-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-xs text-destructive" role="alert">{{ $error }}</p>
    @endif
</div>
