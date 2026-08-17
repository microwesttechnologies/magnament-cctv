<div {{ $attributes->merge(['class' => 'flex w-full min-w-0 flex-col gap-3 rounded-lg border border-border bg-surface p-4 shadow-sm sm:flex-row sm:flex-wrap sm:items-end']) }}>
    {{ $slot }}
</div>
