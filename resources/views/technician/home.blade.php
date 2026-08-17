<x-layout.technician title="Mis órdenes · Management CCTV" active="home">
    <p class="text-sm text-foreground-muted">Hola, {{ $user->name }}</p>
    <h1 class="mt-1 text-2xl font-bold">Tienes {{ $orders->count() }} órdenes activas</h1>

    <div class="mt-5 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('technician.orders.show', $order) }}" class="block rounded-xl border border-border bg-surface p-4 shadow-sm transition duration-fast hover:border-accent">
                <p class="font-mono text-sm font-semibold text-accent">{{ $order->code }}</p>
                <p class="mt-1 text-base font-semibold">{{ $order->project?->name }}</p>
                <p class="mt-1 text-sm text-foreground-muted">{{ $order->description }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.badge :variant="$order->priority === 'alta' ? 'error' : ($order->priority === 'media' ? 'warning' : 'muted')">{{ $order->priorityEnum()->label() }}</x-ui.badge>
                    <x-ui.badge :variant="$order->status === 'en_proceso' ? 'info' : 'warning'">{{ $order->statusEnum()->label() }}</x-ui.badge>
                </div>
            </a>
        @empty
            <x-ui.empty-state title="Sin órdenes activas" description="Cuando te asignen un trabajo aparecerá aquí." />
        @endforelse
    </div>
</x-layout.technician>
