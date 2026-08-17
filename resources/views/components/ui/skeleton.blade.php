@props(['class' => 'h-4 w-full'])

<div {{ $attributes->merge(['class' => "ui-skeleton {$class}", 'aria-hidden' => 'true']) }}></div>
