@php
    $statusVariant = [
        'borrador' => 'muted',
        'emitida' => 'info',
        'aprobada' => 'success',
        'rechazada' => 'error',
        'convertida' => 'accent',
        'cancelada' => 'muted',
    ];
@endphp

<x-layout title="Cotizaciones · CCTV Manager" active="cotizaciones">
    <div x-data="createFormModal({ openOnLoad: @js($openCreateModal), formId: 'quotation-create-form' })">
        @if (session('status'))
            <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any() && old('_form') === 'quotation-create')
            <x-ui.alert variant="error" class="mb-4">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </x-ui.alert>
        @endif

        <x-ui.page-header
            title="Cotizaciones"
            description="Módulo independiente. Crea y gestiona cotizaciones sin pasar por Proyectos."
        >
            <x-slot:actions>
                <x-ui.button type="button" @click="open()">Nueva cotización</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.data-table>
            <thead>
                <tr>
                    <th class="w-[16%]">Código</th>
                    <th class="w-[32%]">Proyecto</th>
                    <th class="w-[14%]">Estado</th>
                    <th class="w-[16%] text-right">Total</th>
                    <th class="w-[10%]">IVA %</th>
                    <th class="w-[12%] text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotations as $quote)
                    <tr>
                        <td class="font-medium">{{ $quote->code }}</td>
                        <td>{{ $quote->project?->name }}</td>
                        <td>
                            <x-ui.badge :variant="$statusVariant[$quote->status] ?? 'muted'" dot>
                                {{ ucfirst($quote->status) }}
                            </x-ui.badge>
                        </td>
                        <td class="text-right font-mono">${{ number_format((float) $quote->total, 0, ',', '.') }}</td>
                        <td>{{ $quote->vat_rate_percent }}%</td>
                        <td class="text-right">
                            <x-ui.button variant="ghost" size="sm" :href="route('projects.quotations.show', [$quote->project_id, $quote->id])">
                                Ver
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-ui.empty-state
                                title="Sin cotizaciones"
                                description="Crea la primera cotización desde el botón Nueva cotización."
                                action-label="Nueva cotización"
                                :action-href="route('cotizaciones', ['crear' => 1])"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.data-table>
        <x-ui.pagination :current="$quotations->currentPage()" :total="$quotations->lastPage()" />

        <x-ui.create-form-modal
            open="createOpen"
            title="Nueva cotización"
            description="Selecciona un proyecto y completa la propuesta comercial."
            max-width="full"
        >
            @include('quotations.partials.standalone-create-form')

            <x-slot:footer>
                <x-ui.button type="button" variant="outline" class="min-h-11" @click="requestClose()">Cancelar</x-ui.button>
                <x-ui.button type="submit" form="quotation-create-form" class="min-h-11">Crear cotización</x-ui.button>
            </x-slot:footer>
        </x-ui.create-form-modal>
    </div>
</x-layout>
