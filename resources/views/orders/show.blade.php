<x-layout title="Orden {{ $order->code }} · CCTV Manager" active="cotizaciones">
    <x-ui.page-header
        :title="$order->code"
        :description="'Proyecto: '.$project->name.' · Origen: cotización '.($order->quotation?->code ?? '—')"
    >
        <x-slot:breadcrumbs>
            <x-ui.breadcrumbs :items="[
                ['label' => 'Proyectos', 'href' => route('projects')],
                ['label' => $project->name, 'href' => route('projects.show', $project)],
                ['label' => $order->code],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-ui.badge variant="info" dot>{{ ucfirst($order->status) }}</x-ui.badge>
            <x-ui.button variant="outline" :href="route('trazabilidad', ['project_id' => $project->id])">Ver trazabilidad</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Orden de Instalación / Implementación" class="lg:col-span-2">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-foreground-muted">Proyecto</dt>
                    <dd class="mt-1 font-medium text-foreground">{{ $project->name }}</dd>
                </div>
                <div>
                    <dt class="text-foreground-muted">Cotización</dt>
                    <dd class="mt-1 font-medium">
                        <a class="text-accent hover:underline" href="{{ route('projects.quotations.show', [$project, $order->quotation_id]) }}">{{ $order->quotation?->code }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-foreground-muted">Total cotizado</dt>
                    <dd class="mt-1 font-mono font-medium">{{ $order->quotation?->total }}</dd>
                </div>
                <div>
                    <dt class="text-foreground-muted">IVA aplicado</dt>
                    <dd class="mt-1 font-mono font-medium">{{ $order->quotation?->vat_rate_percent }}%</dd>
                </div>
            </dl>
            @if ($order->notes)
                <div class="mt-6 border-t border-border-subtle pt-4">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Notas</h3>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-foreground-muted">{{ $order->notes }}</p>
                </div>
            @endif
        </x-ui.card>

        <x-ui.status-card
            :title="'Estado: '.ucfirst($order->status)"
            :status="ucfirst($order->status)"
            variant="info"
            :description="'Creada desde cotización '.($order->quotation?->code ?? '—')"
        >
            <x-slot:actions>
                <x-ui.button variant="outline" class="w-full" :href="route('projects.show', $project)">Ver proyecto</x-ui.button>
                @if ($order->quotation_id)
                    <x-ui.button variant="ghost" class="w-full" :href="route('projects.quotations.show', [$project, $order->quotation_id])">Ver cotización</x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.status-card>
    </div>
</x-layout>
