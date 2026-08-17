<x-layout title="Trazabilidad · CCTV Manager" active="trazabilidad">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Trazabilidad</h1>
            <p class="mt-1 text-slate-500">Eventos de cotizaciones y órdenes vinculados a proyectos.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="project_id" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Todos los proyectos</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected((int) $selectedProjectId === (int) $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
        </form>
    </div>

    <ol class="mt-8 space-y-4">
        @forelse ($events as $event)
            <li class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $event->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $event->event_type }} · {{ $event->project?->name }} · {{ $event->created_at }}</p>
                    </div>
                    @if ($event->quotation_id)
                        <a class="text-sm text-blue-600 hover:underline" href="{{ route('projects.quotations.show', [$event->project_id, $event->quotation_id]) }}">Ver cotización</a>
                    @endif
                </div>
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-slate-200 px-5 py-10 text-center text-slate-500">Sin eventos aún.</li>
        @endforelse
    </ol>
</x-layout>
