<div
    aria-live="polite"
    aria-atomic="true"
    class="pointer-events-none fixed inset-x-0 top-4 z-[80] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end"
>
    <template x-for="item in $store.notifications.items" :key="item.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-enter duration-medium motion-reduce:transition-none"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-exit duration-fast motion-reduce:transition-none"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-border bg-surface p-4 shadow-md"
            :class="{
                'border-l-4 border-l-success': item.type === 'success',
                'border-l-4 border-l-info': item.type === 'info',
                'border-l-4 border-l-warning': item.type === 'warning',
                'border-l-4 border-l-destructive': item.type === 'error',
            }"
            role="alert"
        >
            <p class="min-w-0 flex-1 text-sm text-foreground" x-text="item.message"></p>
            <button
                type="button"
                class="shrink-0 text-foreground-muted hover:text-foreground ui-focus"
                @click="$store.notifications.dismiss(item.id)"
                aria-label="Cerrar notificación"
            >&times;</button>
        </div>
    </template>
</div>
