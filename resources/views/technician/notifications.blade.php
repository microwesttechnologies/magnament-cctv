<x-layout.technician title="Notificaciones · Management CCTV" active="notifications">
    <h1 class="text-2xl font-bold">Notificaciones</h1>
    <div class="mt-5 space-y-3">
        @forelse ($notifications as $item)
            <a href="{{ $item->url ?: route('technician.home') }}" class="block rounded-xl border border-border bg-surface p-4">
                <p class="font-semibold">{{ $item->title }}</p>
                <p class="mt-1 text-sm text-foreground-muted">{{ $item->body }}</p>
                <p class="mt-2 text-xs text-foreground-muted">{{ $item->created_at?->diffForHumans() }}</p>
            </a>
        @empty
            <x-ui.empty-state title="Sin avisos" description="Cuando te asignen o reasignen un trabajo aparecerá aquí." />
        @endforelse
    </div>
    <x-ui.pagination :current="$notifications->currentPage()" :total="$notifications->lastPage()" />
</x-layout.technician>
