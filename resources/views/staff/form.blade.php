@php
    $isEdit = $staff->exists;
@endphp

<x-layout :title="($isEdit ? 'Editar' : 'Crear').' personal · CCTV Manager'" active="personal">
    <x-ui.page-header
        :title="$isEdit ? 'Editar personal' : 'Agregar personal'"
        description="Datos del colaborador y asignación de herramientas"
    >
        <x-slot:breadcrumbs>
            <x-ui.button variant="ghost" size="sm" href="{{ route('staff.index') }}">← Volver al listado</x-ui.button>
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    @if ($errors->any())
        <x-ui.alert variant="error" class="mb-6">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <form
        method="POST"
        action="{{ $isEdit ? route('staff.update', $staff) : route('staff.store') }}"
        enctype="multipart/form-data"
        class="space-y-6"
        x-data="staffForm({{ \Illuminate\Support\Js::from(old('tools', $tools)) }})"
    >
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.card title="Datos personales">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.form-field label="Nombre completo" required class="sm:col-span-2">
                    <x-ui.input type="text" name="name" value="{{ old('name', $staff->name) }}" required />
                </x-ui.form-field>
                <x-ui.form-field label="Tipo de documento">
                    <x-ui.select name="document_type">
                        @foreach (['CC', 'CE', 'Pasaporte', 'PPT'] as $doc)
                            <option value="{{ $doc }}" @selected(old('document_type', $staff->document_type) === $doc)>{{ $doc }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.form-field label="Cédula / Número" required>
                    <x-ui.input type="text" name="document_number" value="{{ old('document_number', $staff->document_number) }}" required />
                </x-ui.form-field>
                <x-ui.form-field label="Celular">
                    <x-ui.input type="text" name="phone" value="{{ old('phone', $staff->phone) }}" />
                </x-ui.form-field>
                <x-ui.form-field label="Correo">
                    <x-ui.input type="email" name="email" value="{{ old('email', $staff->email) }}" />
                </x-ui.form-field>
                <x-ui.form-field label="Ciudad">
                    <x-ui.input type="text" name="city" value="{{ old('city', $staff->city) }}" />
                </x-ui.form-field>
                <x-ui.form-field label="Fecha de nacimiento">
                    <x-ui.input type="date" name="birth_date" value="{{ old('birth_date', optional($staff->birth_date)->format('Y-m-d')) }}" />
                </x-ui.form-field>
                <x-ui.form-field label="Rol">
                    <x-ui.select name="role">
                        <option value="tecnico" @selected(old('role', $staff->role) === 'tecnico')>Técnico</option>
                        <option value="supervisor" @selected(old('role', $staff->role) === 'supervisor')>Supervisor</option>
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.form-field label="Estado">
                    <x-ui.select name="status">
                        <option value="activo" @selected(old('status', $staff->status) === 'activo')>Activo</option>
                        <option value="inactivo" @selected(old('status', $staff->status) === 'inactivo')>Inactivo</option>
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.form-field label="Foto" class="sm:col-span-2">
                    <div class="flex items-center gap-4">
                        @if ($staff->photoUrl())
                            <img src="{{ $staff->photoUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover">
                        @endif
                        <input
                            type="file"
                            name="photo"
                            accept=".png,.jpg,.jpeg"
                            class="block w-full text-sm text-foreground-muted file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-semibold file:text-on-accent"
                        >
                    </div>
                </x-ui.form-field>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-base font-semibold text-foreground">Asignación de herramientas</h2>
                <x-ui.button type="button" variant="outline" size="sm" @click="addTool()">+ Agregar</x-ui.button>
            </x-slot:header>

            <x-ui.data-table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Referencia</th>
                        <th>Serie</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(tool, i) in tools" :key="tool.key">
                        <tr>
                            <td><x-ui.input type="text" x-bind:name="'tools['+i+'][name]'" x-model="tool.name" placeholder="Taladro" /></td>
                            <td><x-ui.input type="text" x-bind:name="'tools['+i+'][brand]'" x-model="tool.brand" placeholder="Bosch" /></td>
                            <td><x-ui.input type="text" x-bind:name="'tools['+i+'][reference]'" x-model="tool.reference" placeholder="Opcional" /></td>
                            <td><x-ui.input type="text" x-bind:name="'tools['+i+'][serial]'" x-model="tool.serial" placeholder="Opcional" /></td>
                            <td class="text-right">
                                <x-ui.button type="button" variant="ghost" size="sm" @click="removeTool(i)" class="text-destructive hover:text-destructive">✕</x-ui.button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </x-ui.data-table>
            <p x-show="tools.length === 0" class="py-6 text-center text-sm text-foreground-muted">Sin herramientas. Usa “Agregar”.</p>
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.button variant="outline" href="{{ route('staff.index') }}">Cancelar</x-ui.button>
            <x-ui.button type="submit">{{ $isEdit ? 'Guardar cambios' : 'Crear personal' }}</x-ui.button>
        </div>
    </form>

    <script>
        function staffForm(initial) {
            let key = 1;
            const tools = (initial || []).map(t => ({ key: key++, name: t.name || '', brand: t.brand || '', reference: t.reference || '', serial: t.serial || '' }));
            return {
                tools,
                addTool() {
                    this.tools.push({ key: key++, name: '', brand: '', reference: '', serial: '' });
                },
                removeTool(i) {
                    this.tools.splice(i, 1);
                },
            };
        }
    </script>
</x-layout>
