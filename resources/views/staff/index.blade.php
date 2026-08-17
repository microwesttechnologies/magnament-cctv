@php
    $roleLabels = ['supervisor' => 'Supervisor', 'tecnico' => 'Técnico'];
    $statusVariant = [
        'activo' => 'success',
        'inactivo' => 'muted',
    ];
@endphp

<x-layout title="Personal · CCTV Manager" active="personal">
    <div x-data="createFormModal({ openOnLoad: @js($openCreateModal), formId: 'staff-create-form' })">
        @if (session('status'))
            <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any() && old('_form') === 'staff-create')
            <x-ui.alert variant="error" class="mb-4">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </x-ui.alert>
        @endif

        <x-ui.page-header
            title="Personal"
            description="Supervisores y técnicos de la empresa"
        >
            <x-slot:actions>
                <x-ui.button type="button" @click="open()">Agregar personal</x-ui.button>
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
                                :action-href="route('staff.index', ['crear' => 1])"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.data-table>
        <x-ui.pagination :current="$staff->currentPage()" :total="$staff->lastPage()" />

        <x-ui.create-form-modal
            open="createOpen"
            title="Agregar personal"
            description="Datos del colaborador y asignación de herramientas."
            max-width="lg"
        >
            <form
                id="staff-create-form"
                method="POST"
                action="{{ route('staff.store') }}"
                enctype="multipart/form-data"
                data-create-modal-form
                x-data="staffForm(@js(old('tools', [])))"
            >
                @csrf
                <input type="hidden" name="_form" value="staff-create">
                <div class="space-y-6">
                    <x-ui.card title="Datos personales">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.form-field label="Nombre completo" required class="sm:col-span-2">
                                <x-ui.input type="text" name="name" value="{{ old('name') }}" required class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <x-ui.form-field label="Tipo de documento">
                                <x-ui.select name="document_type" class="min-h-11 w-full">
                                    @foreach (['CC', 'CE', 'Pasaporte', 'PPT'] as $doc)
                                        <option value="{{ $doc }}" @selected(old('document_type', 'CC') === $doc)>{{ $doc }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.form-field>
                            <x-ui.form-field label="Cédula / Número" required>
                                <x-ui.input type="text" name="document_number" value="{{ old('document_number') }}" required class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <x-ui.form-field label="Celular">
                                <x-ui.input type="text" name="phone" value="{{ old('phone') }}" class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <x-ui.form-field label="Correo">
                                <x-ui.input type="email" name="email" value="{{ old('email') }}" class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <x-ui.form-field label="Ciudad">
                                <x-ui.input type="text" name="city" value="{{ old('city') }}" class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <x-ui.form-field label="Fecha de nacimiento">
                                <x-ui.input type="date" name="birth_date" value="{{ old('birth_date') }}" class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <x-ui.form-field label="Rol">
                                <x-ui.select name="role" class="min-h-11 w-full">
                                    <option value="tecnico" @selected(old('role', 'tecnico') === 'tecnico')>Técnico</option>
                                    <option value="supervisor" @selected(old('role') === 'supervisor')>Supervisor</option>
                                </x-ui.select>
                            </x-ui.form-field>
                            <x-ui.form-field label="Estado">
                                <x-ui.select name="status" class="min-h-11 w-full">
                                    <option value="activo" @selected(old('status', 'activo') === 'activo')>Activo</option>
                                    <option value="inactivo" @selected(old('status') === 'inactivo')>Inactivo</option>
                                </x-ui.select>
                            </x-ui.form-field>
                            <x-ui.form-field label="Foto" class="sm:col-span-2">
                                <input type="file" name="photo" accept=".png,.jpg,.jpeg" class="block w-full text-sm text-foreground-muted file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-semibold file:text-on-accent">
                            </x-ui.form-field>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <x-slot:header>
                            <h2 class="text-base font-semibold text-foreground">Asignación de herramientas</h2>
                            <x-ui.button type="button" variant="outline" size="sm" @click="addTool()">+ Agregar</x-ui.button>
                        </x-slot:header>
                        <div class="space-y-3">
                            <template x-for="(tool, i) in tools" :key="tool.key">
                                <div class="grid grid-cols-1 gap-3 rounded-lg border border-border-subtle p-3 sm:grid-cols-2">
                                    <x-ui.input type="text" x-bind:name="'tools['+i+'][name]'" x-model="tool.name" placeholder="Nombre" class="min-h-11 w-full" />
                                    <x-ui.input type="text" x-bind:name="'tools['+i+'][brand]'" x-model="tool.brand" placeholder="Marca" class="min-h-11 w-full" />
                                    <x-ui.input type="text" x-bind:name="'tools['+i+'][reference]'" x-model="tool.reference" placeholder="Referencia" class="min-h-11 w-full" />
                                    <div class="flex gap-2">
                                        <x-ui.input type="text" x-bind:name="'tools['+i+'][serial]'" x-model="tool.serial" placeholder="Serie" class="min-h-11 w-full" />
                                        <x-ui.button type="button" variant="ghost" size="sm" @click="removeTool(i)" class="text-destructive">✕</x-ui.button>
                                    </div>
                                </div>
                            </template>
                            <p x-show="tools.length === 0" class="py-4 text-center text-sm text-foreground-muted">Sin herramientas. Usa “Agregar”.</p>
                        </div>
                    </x-ui.card>
                </div>
            </form>

            <x-slot:footer>
                <x-ui.button type="button" variant="outline" class="min-h-11" @click="requestClose()">Cancelar</x-ui.button>
                <x-ui.button type="submit" form="staff-create-form" class="min-h-11">Crear personal</x-ui.button>
            </x-slot:footer>
        </x-ui.create-form-modal>
    </div>
</x-layout>
