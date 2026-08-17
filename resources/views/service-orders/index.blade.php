@php
    $statusVariant = [
        'pendiente' => 'muted',
        'asignada' => 'warning',
        'en_proceso' => 'info',
        'resuelta' => 'success',
        'cancelada' => 'error',
    ];
    $priorityVariant = [
        'baja' => 'muted',
        'media' => 'warning',
        'alta' => 'error',
    ];
@endphp

<x-layout title="Órdenes de servicio · CCTV Manager" active="ordenes">
    <div x-data="createFormModal({ openOnLoad: @js($openCreateModal), formId: 'service-order-create-form' })">
        @if (session('status'))
            <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any() && old('_form') === 'service-order-create')
            <x-ui.alert variant="error" class="mb-4">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </x-ui.alert>
        @endif

        <x-ui.page-header
            title="Órdenes de servicio"
            description="Trabajos técnicos asignables, con evidencia y trazabilidad."
        >
            <x-slot:actions>
                <x-ui.button type="button" @click="open()">Nueva orden</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="motion-stagger mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
            <x-ui.stat-card label="Pendientes" :value="(string) $counts['pendiente']" />
            <x-ui.stat-card label="Asignadas" :value="(string) $counts['asignada']" />
            <x-ui.stat-card label="En proceso" :value="(string) $counts['en_proceso']" />
            <x-ui.stat-card label="Resueltas" :value="(string) $counts['resuelta']" />
            <x-ui.stat-card label="Canceladas" :value="(string) $counts['cancelada']" />
        </div>

        <form method="GET" action="{{ route('service-orders.index') }}" class="mb-6">
            <x-ui.filter-bar>
                <x-ui.form-field label="Buscar" class="min-w-48 flex-1">
                    <x-ui.search name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código o descripción…" />
                </x-ui.form-field>
                <x-ui.form-field label="Estado">
                    <x-ui.select name="status">
                        <option value="">Todos</option>
                        @foreach (['pendiente' => 'Pendiente', 'asignada' => 'Asignada', 'en_proceso' => 'En proceso', 'resuelta' => 'Resuelta', 'cancelada' => 'Cancelada'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.form-field label="Prioridad">
                    <x-ui.select name="priority">
                        <option value="">Todas</option>
                        @foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
            </x-ui.filter-bar>
        </form>

        <x-ui.data-table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proyecto</th>
                    <th>Descripción</th>
                    <th>Técnico</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th class="text-right">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-mono font-medium">{{ $order->code }}</td>
                        <td>{{ $order->project?->name }}</td>
                        <td class="max-w-xs truncate">{{ $order->description }}</td>
                        <td>{{ $order->technician?->name ?? 'Sin asignar' }}</td>
                        <td><x-ui.badge :variant="$priorityVariant[$order->priority] ?? 'muted'">{{ $order->priorityEnum()->label() }}</x-ui.badge></td>
                        <td><x-ui.badge :variant="$statusVariant[$order->status] ?? 'muted'" dot>{{ $order->statusEnum()->label() }}</x-ui.badge></td>
                        <td class="text-right">
                            <x-ui.button variant="ghost" size="sm" :href="route('service-orders.show', $order)">Ver</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-ui.empty-state
                                title="Sin órdenes"
                                description="Crea la primera orden de servicio para un proyecto."
                                action-label="Nueva orden"
                                :action-href="route('service-orders.index', ['crear' => 1])"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.data-table>
        <x-ui.pagination :current="$orders->currentPage()" :total="$orders->lastPage()" />

        <x-ui.create-form-modal
            open="createOpen"
            title="Nueva orden de servicio"
            description="Asigna un trabajo técnico."
            max-width="lg"
        >
            <form
                id="service-order-create-form"
                method="POST"
                action="{{ route('service-orders.store') }}"
                data-create-modal-form
            >
                @csrf
                <input type="hidden" name="_form" value="service-order-create">
                <x-forms.service-order-fields
                    :projects="$projects"
                    :technicians="$technicians"
                    :selected-project-id="$selectedProjectId"
                />
            </form>

            <x-slot:footer>
                <x-ui.button type="button" variant="outline" class="min-h-11" @click="requestClose()">Cancelar</x-ui.button>
                <x-ui.button type="submit" form="service-order-create-form" class="min-h-11">Crear orden</x-ui.button>
            </x-slot:footer>
        </x-ui.create-form-modal>
    </div>
</x-layout>
