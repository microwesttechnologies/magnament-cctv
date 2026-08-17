<x-layout.technician title="Mis órdenes · Management CCTV" active="orders">
    <h1 class="text-2xl font-bold">Mis órdenes</h1>
    <p class="mt-1 text-sm text-foreground-muted">Solo ves los trabajos asignados a ti.</p>

    <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
        @foreach ([
            'todas' => 'Todas',
            'pendiente' => 'Pendientes',
            'asignada' => 'Asignadas',
            'en_proceso' => 'En proceso',
            'resuelta' => 'Resueltas',
            'no_resuelta' => 'No resueltas',
            'cancelada' => 'Canceladas',
        ] as $value => $label)
            <a
                href="{{ route('technician.orders.index', ['status' => $value]) }}"
                class="min-h-11 shrink-0 rounded-full border px-4 py-2 text-sm font-medium {{ $status === $value ? 'border-accent bg-accent/10 text-accent' : 'border-border text-foreground-muted' }}"
            >{{ $label }}</a>
        @endforeach
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($orders as $order)
            <article class="rounded-xl border border-border bg-surface p-4 shadow-sm">
                <p class="font-mono text-sm font-semibold text-accent">{{ $order->code }}</p>
                <p class="mt-1 text-base font-semibold">{{ $order->project?->name }}</p>
                <p class="mt-1 text-sm text-foreground-muted">{{ $order->description }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.badge :variant="$order->priority === 'alta' ? 'error' : ($order->priority === 'media' ? 'warning' : 'muted')">{{ $order->priorityEnum()->label() }}</x-ui.badge>
                    <x-ui.badge :variant="$order->status === 'en_proceso' ? 'info' : ($order->status === 'resuelta' ? 'success' : ($order->status === 'no_resuelta' ? 'warning' : ($order->status === 'cancelada' ? 'error' : 'warning')))">{{ $order->statusEnum()->label() }}</x-ui.badge>
                </div>
                <x-ui.button class="mt-4 min-h-11 w-full justify-center" :href="route('technician.orders.show', $order)">Ver orden</x-ui.button>
            </article>
        @empty
            <x-ui.empty-state title="Sin órdenes" description="No hay órdenes en este filtro." />
        @endforelse
    </div>
    <x-ui.pagination :current="$orders->currentPage()" :total="$orders->lastPage()" />
</x-layout.technician>
