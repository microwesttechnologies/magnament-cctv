@php
    $roleLabels = ['supervisor' => 'Supervisor', 'tecnico' => 'Técnico'];
    $statusVariant = [
        'activo' => 'success',
        'inactivo' => 'muted',
    ];
@endphp

<x-layout title="Personal · CCTV Manager" active="personal">
    @if (session('status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert variant="error" class="mb-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <x-ui.page-header
        title="Personal"
        description="Supervisores y técnicos de la empresa"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('staff.create') }}">Agregar personal</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mb-6">
        <form method="GET" class="grid w-full grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_12rem_auto]">
            <x-ui.form-field label="Buscar" class="min-w-0 sm:col-span-2 lg:col-span-1">
                <x-ui.input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Nombre, cédula o correo"
                />
            </x-ui.form-field>
            <x-ui.form-field label="Rol">
                <x-ui.select name="role">
                    <option value="">Todos</option>
                    <option value="supervisor" @selected(($filters['role'] ?? '') === 'supervisor')>Supervisor</option>
                    <option value="tecnico" @selected(($filters['role'] ?? '') === 'tecnico')>Técnico</option>
                </x-ui.select>
            </x-ui.form-field>
            <x-ui.form-field label="Estado">
                <x-ui.select name="status">
                    <option value="">Todos</option>
                    <option value="activo" @selected(($filters['status'] ?? '') === 'activo')>Activo</option>
                    <option value="inactivo" @selected(($filters['status'] ?? '') === 'inactivo')>Inactivo</option>
                </x-ui.select>
            </x-ui.form-field>
            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.data-table>
        <thead>
            <tr>
                <th class="w-[22%]">Nombre</th>
                <th class="w-[14%]">Documento</th>
                <th class="w-[10%]">Rol</th>
                <th class="w-[20%]">Contacto</th>
                <th class="w-[10%]">Estado</th>
                <th class="w-[10%]">Herramientas</th>
                <th class="w-[14%] text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($staff as $person)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 overflow-hidden rounded-full bg-muted">
                                @if ($person->photoUrl())
                                    <img src="{{ $person->photoUrl() }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-foreground-muted">{{ strtoupper(substr($person->name, 0, 1)) }}</div>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-foreground">{{ $person->name }}</p>
                                <p class="text-xs text-foreground-muted">{{ $person->city ?: '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-foreground-muted">{{ $person->document_type }} {{ $person->document_number }}</td>
                    <td class="text-foreground-muted">{{ $roleLabels[$person->role] ?? $person->role }}</td>
                    <td class="text-foreground-muted">
                        <p>{{ $person->phone ?: '—' }}</p>
                        <p class="text-xs text-foreground-muted">{{ $person->email ?: '' }}</p>
                    </td>
                    <td>
                        <x-ui.badge :variant="$statusVariant[$person->status] ?? 'muted'" dot>
                            {{ ucfirst($person->status) }}
                        </x-ui.badge>
                    </td>
                    <td class="font-mono text-foreground-muted">{{ $person->tools_count }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('staff.edit', $person) }}">Editar</x-ui.button>
                            <form method="POST" action="{{ route('staff.destroy', $person) }}" onsubmit="return confirm('¿Eliminar a {{ $person->name }}?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="destructive" size="sm">Eliminar</x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state
                            title="No hay personal registrado"
                            description="Agrega supervisores y técnicos para gestionar asignaciones."
                            action-label="Agregar personal"
                            :action-href="route('staff.create')"
                        />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.data-table>
    <x-ui.pagination :current="$staff->currentPage()" :total="$staff->lastPage()" />
</x-layout>
