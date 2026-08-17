@php
    $statusVariant = [
        'borrador' => 'muted',
        'emitida' => 'info',
        'aprobada' => 'success',
        'rechazada' => 'error',
        'convertida' => 'accent',
        'cancelada' => 'muted',
    ];
    $variant = $statusVariant[$quotation->status()->value] ?? 'muted';
@endphp

<x-layout title="Cotización {{ $quotation->code() }} · CCTV Manager" active="cotizaciones">
    <x-ui.page-header :title="$quotation->code()" :description="'Proyecto: '.$project->name">
        <x-slot:breadcrumbs>
            <x-ui.breadcrumbs :items="[
                ['label' => 'Cotizaciones', 'href' => route('cotizaciones')],
                ['label' => $project->name, 'href' => route('projects.show', $project)],
                ['label' => $quotation->code()],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-ui.badge :variant="$variant" dot>{{ ucfirst($quotation->status()->value) }}</x-ui.badge>
            <x-ui.button variant="outline" data-no-motion :href="route('projects.quotations.pdf', [$project, $quotation->id()->value()])">Descargar PDF</x-ui.button>
            @if ($quotation->status()->isEditable())
                <x-ui.button :href="route('projects.quotations.edit', [$project, $quotation->id()->value()])">Editar</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert variant="error" class="mb-6">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Descripción del trabajo">
                <p class="max-w-prose whitespace-pre-wrap text-sm text-foreground-muted">{{ $quotation->workDescription() }}</p>
            </x-ui.card>

            <x-ui.card title="Líneas de cotización" :padding="false">
                <x-ui.data-table>
                    <thead>
                        <tr>
                            <th class="w-[32%]">Producto</th>
                            <th class="w-[16%]">Marca</th>
                            <th class="w-[16%]">Serie</th>
                            <th class="w-[12%] text-right">Cant.</th>
                            <th class="w-[12%] text-right">P. unit.</th>
                            <th class="w-[12%] text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotation->lines() as $line)
                            <tr>
                                <td class="font-medium">{{ $line->productName() }}</td>
                                <td>{{ $line->brand() ?? '—' }}</td>
                                <td>{{ $line->serial() ?? '—' }}</td>
                                <td class="text-right font-mono">{{ $line->quantity() }}</td>
                                <td class="text-right font-mono">{{ $line->unitPrice()->amount() }}</td>
                                <td class="text-right font-mono">{{ $line->lineSubtotal()->amount() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.data-table>
            </x-ui.card>

            <x-ui.card title="Historial">
                @if ($history->isEmpty())
                    <x-ui.empty-state title="Sin eventos" description="Sin eventos de auditoría registrados." />
                @else
                    <ul class="divide-y divide-border-subtle">
                        @foreach ($history as $entry)
                            <li class="py-3 first:pt-0">
                                <p class="text-sm font-medium text-foreground">{{ $entry->action }}</p>
                                <p class="mt-0.5 text-xs text-foreground-muted">{{ $entry->created_at }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Totales">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-foreground-muted">Subtotal</dt><dd class="font-mono">{{ $quotation->subtotal()->amount() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-foreground-muted">IVA ({{ $quotation->vatRate()->percent() }}%)</dt><dd class="font-mono">{{ $quotation->vatAmount()->amount() }}</dd></div>
                    <div class="flex justify-between border-t border-border-subtle pt-2 text-base font-semibold"><dt>Total</dt><dd class="font-mono">{{ $quotation->total()->amount() }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Acciones de estado">
                <div class="space-y-2">
                    @foreach ($quotation->status()->allowedTransitions() as $next)
                        @if ($next->value !== 'convertida')
                            <form method="POST" action="{{ route('projects.quotations.transition', [$project, $quotation->id()->value()]) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $next->value }}">
                                <x-ui.button type="submit" variant="outline" class="w-full capitalize">Pasar a {{ $next->value }}</x-ui.button>
                            </form>
                        @endif
                    @endforeach

                    @if ($quotation->status()->canConvertToOrder() && ! $model->installationOrder)
                        <form method="POST" action="{{ route('projects.quotations.convert', [$project, $quotation->id()->value()]) }}">
                            @csrf
                            <x-ui.button type="submit" class="w-full">Convertir a Orden de Instalación</x-ui.button>
                        </form>
                    @endif

                    @if ($model->installationOrder)
                        <x-ui.button class="w-full" :href="route('projects.orders.show', [$project, $model->installationOrder->id])">
                            Ver orden {{ $model->installationOrder->code }}
                        </x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layout>
