@php
    $isEdit = $staff->exists;
@endphp

<x-layout :title="($isEdit ? 'Editar' : 'Crear').' personal · CCTV Manager'" active="personal">
    <a href="{{ route('staff.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        Volver al listado
    </a>

    <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ $isEdit ? 'Editar personal' : 'Agregar personal' }}</h1>
    <p class="mt-1 text-slate-500">Datos del colaborador y asignación de herramientas</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form
        method="POST"
        action="{{ $isEdit ? route('staff.update', $staff) : route('staff.store') }}"
        enctype="multipart/form-data"
        class="mt-6 space-y-6"
        x-data="staffForm({{ \Illuminate\Support\Js::from(old('tools', $tools)) }})"
    >
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Datos personales</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name', $staff->name) }}" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tipo de documento</label>
                    <select name="document_type" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        @foreach (['CC', 'CE', 'Pasaporte', 'PPT'] as $doc)
                            <option value="{{ $doc }}" @selected(old('document_type', $staff->document_type) === $doc)>{{ $doc }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Cédula / Número</label>
                    <input type="text" name="document_number" value="{{ old('document_number', $staff->document_number) }}" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Celular</label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Correo</label>
                    <input type="email" name="email" value="{{ old('email', $staff->email) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Ciudad</label>
                    <input type="text" name="city" value="{{ old('city', $staff->city) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Fecha de nacimiento</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', optional($staff->birth_date)->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Rol</label>
                    <select name="role" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="tecnico" @selected(old('role', $staff->role) === 'tecnico')>Técnico</option>
                        <option value="supervisor" @selected(old('role', $staff->role) === 'supervisor')>Supervisor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Estado</label>
                    <select name="status" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="activo" @selected(old('status', $staff->status) === 'activo')>Activo</option>
                        <option value="inactivo" @selected(old('status', $staff->status) === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Foto</label>
                    <div class="mt-1.5 flex items-center gap-4">
                        @if ($staff->photoUrl())
                            <img src="{{ $staff->photoUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover">
                        @endif
                        <input type="file" name="photo" accept=".png,.jpg,.jpeg" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Asignación de herramientas</h2>
                <button type="button" @click="addTool()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">+ Agregar</button>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-2 py-2">Nombre</th>
                            <th class="px-2 py-2">Marca</th>
                            <th class="px-2 py-2">Referencia</th>
                            <th class="px-2 py-2">Serie</th>
                            <th class="px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(tool, i) in tools" :key="tool.key">
                            <tr class="border-b border-slate-100">
                                <td class="px-2 py-2"><input type="text" :name="'tools['+i+'][name]'" x-model="tool.name" class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm" placeholder="Taladro"></td>
                                <td class="px-2 py-2"><input type="text" :name="'tools['+i+'][brand]'" x-model="tool.brand" class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm" placeholder="Bosch"></td>
                                <td class="px-2 py-2"><input type="text" :name="'tools['+i+'][reference]'" x-model="tool.reference" class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm" placeholder="Opcional"></td>
                                <td class="px-2 py-2"><input type="text" :name="'tools['+i+'][serial]'" x-model="tool.serial" class="w-full rounded border border-slate-200 px-2 py-1.5 text-sm" placeholder="Opcional"></td>
                                <td class="px-2 py-2 text-right"><button type="button" @click="removeTool(i)" class="text-red-500 hover:text-red-700">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p x-show="tools.length === 0" class="py-6 text-center text-sm text-slate-400">Sin herramientas. Usa “Agregar”.</p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('staff.index') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600">Cancelar</a>
            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">{{ $isEdit ? 'Guardar cambios' : 'Crear personal' }}</button>
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
