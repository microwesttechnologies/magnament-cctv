<x-layout title="Panel · CCTV Manager" active="home">
    <x-ui.page-header
        title="Bienvenido, {{ auth()->user()->name }}"
        description="Resumen operativo de proyectos, cotizaciones y trazabilidad."
    >
        <x-slot:actions>
            <x-ui.button variant="outline" href="{{ route('projects') }}">Nuevo proyecto</x-ui.button>
            <x-ui.button href="{{ route('quotations.create') }}">Nueva Cotización</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Proyectos activos" :value="(string) $stats['projects_active']" />
        <x-ui.stat-card label="En instalación" :value="(string) $stats['projects_installing']" />
        <x-ui.stat-card label="Cotizaciones pendientes" :value="(string) $stats['quotations_pending']" />
        <x-ui.stat-card label="Órdenes abiertas" :value="(string) $stats['orders_open']" />
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card title="Requiere atención" class="lg:col-span-2">
            <x-ui.data-table>
                <thead>
                    <tr>
                        <th class="w-[22%]">Código</th>
                        <th class="w-[38%]">Proyecto</th>
                        <th class="w-[22%]">Estado</th>
                        <th class="w-[18%] text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attention as $quote)
                        <tr>
                            <td class="font-medium">{{ $quote->code }}</td>
                            <td>{{ $quote->project?->name ?? '—' }}</td>
                            <td><x-ui.badge variant="warning" dot>{{ ucfirst($quote->status) }}</x-ui.badge></td>
                            <td class="text-right">
                                <x-ui.button variant="ghost" size="sm" :href="route('projects.quotations.show', [$quote->project_id, $quote->id])">Ver</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state title="Sin pendientes" description="No hay cotizaciones que requieran atención inmediata." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.data-table>
        </x-ui.card>

        <x-ui.card title="Acciones rápidas">
            <div class="space-y-2">
                <x-ui.button class="w-full justify-center" href="{{ route('quotations.create') }}">Nueva cotización</x-ui.button>
                <x-ui.button class="w-full justify-center" variant="secondary" href="{{ route('projects') }}">Nuevo proyecto</x-ui.button>
                <x-ui.button class="w-full justify-center" variant="outline" href="{{ route('cotizaciones') }}">Ver cotizaciones pendientes</x-ui.button>
                <x-ui.button class="w-full justify-center" variant="ghost" href="{{ route('trazabilidad') }}">Ver trazabilidad</x-ui.button>
            </div>
        </x-ui.card>
    </div>

    <x-ui.card title="Actividad reciente" class="mt-6 w-full">
        @if ($recentActivity->isEmpty())
            <x-ui.empty-state title="Sin actividad" description="Los eventos de trazabilidad aparecerán aquí." />
        @else
            <ul class="divide-y divide-border-subtle">
                @foreach ($recentActivity as $event)
                    <li class="flex flex-wrap items-start justify-between gap-2 py-3">
                        <div>
                            <p class="text-sm font-medium text-foreground">{{ $event->title }}</p>
                            <p class="mt-0.5 text-xs text-foreground-muted">
                                {{ $event->project?->name ?? 'Sistema' }}
                                · {{ $event->created_at?->diffForHumans() }}
                            </p>
                        </div>
                        <x-ui.badge variant="muted">{{ $event->event_type }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</x-layout>
