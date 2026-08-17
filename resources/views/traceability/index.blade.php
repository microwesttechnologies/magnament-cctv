<x-layout title="Trazabilidad · CCTV Manager" active="trazabilidad">
    <x-ui.page-header
        title="Trazabilidad"
        description="Eventos de cotizaciones y órdenes vinculados a proyectos."
    >
        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <x-ui.select name="project_id" class="min-w-48">
                    <option value="">Todos los proyectos</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected((int) $selectedProjectId === (int) $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($events->isEmpty())
        <x-ui.empty-state
            title="Sin eventos aún"
            description="Los eventos de cotizaciones y órdenes aparecerán aquí conforme avance el proyecto."
        />
    @else
        <ul class="space-y-4">
            @foreach ($events as $event)
                <li>
                    <x-ui.card>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-foreground">{{ $event->title }}</p>
                                <p class="mt-1 text-xs text-foreground-muted">
                                    <x-ui.badge variant="muted">{{ $event->event_type }}</x-ui.badge>
                                    · {{ $event->project?->name }}
                                    · {{ $event->created_at }}
                                </p>
                            </div>
                            @if ($event->quotation_id)
                                <x-ui.button variant="ghost" size="sm" :href="route('projects.quotations.show', [$event->project_id, $event->quotation_id])">
                                    Ver cotización
                                </x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>
