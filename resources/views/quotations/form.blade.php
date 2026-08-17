@php
    $standalone = $standalone ?? false;
    $projects = $projects ?? collect();
    $initialLines = old('lines');
    if (! is_array($initialLines)) {
        if ($quotation) {
            $initialLines = collect($quotation->lines())->map(fn ($l) => [
                'product_name' => $l->productName(),
                'quantity' => $l->quantity(),
                'brand' => $l->brand() ?? '',
                'serial' => $l->serial() ?? '',
                'unit_price' => $l->unitPrice()->amount(),
            ])->values()->all();
        } else {
            $initialLines = [['product_name' => '', 'quantity' => '1', 'brand' => '', 'serial' => '', 'unit_price' => '0']];
        }
    }
    $formAction = $quotation
        ? route('projects.quotations.update', [$project, $quotation->id()->value()])
        : ($standalone ? route('quotations.store') : route('projects.quotations.store', $project));
    $cancelUrl = $standalone ? route('cotizaciones') : route('projects.show', $project);
@endphp

<x-layout :title="($quotation ? 'Editar' : 'Nueva').' cotización · CCTV Manager'" active="cotizaciones">
    <div class="max-w-4xl">
        <h1 class="text-3xl font-bold tracking-tight">{{ $quotation ? 'Editar cotización' : 'Nueva cotización' }}</h1>
        @unless ($standalone)
            <p class="mt-1 text-slate-500">Proyecto: <strong>{{ $project->name }}</strong> ({{ $project->code }})</p>
        @endunless

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form
            method="POST"
            action="{{ $formAction }}"
            class="mt-6 space-y-6"
            x-data="quotationForm({
                lines: @js($initialLines),
                projects: @js($projects->map(fn ($p) => ['id' => (int) $p->id, 'name' => $p->name, 'code' => $p->code])->values()),
                selectedId: @js((string) old('project_id', $project?->id ?? '')),
                createUrl: @js(route('quotations.projects.store')),
                csrf: @js(csrf_token()),
                standalone: @js($standalone && ! $quotation),
            })"
        >
            @csrf
            @if ($quotation) @method('PUT') @endif

            @if ($standalone && ! $quotation)
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <label class="block text-sm font-medium text-slate-700">Proyecto</label>
                    <p class="mt-1 text-sm text-slate-500">Busca un proyecto existente o crea uno nuevo desde el selector.</p>
                    <input
                        type="search"
                        x-model="projectQuery"
                        placeholder="Buscar proyecto…"
                        class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    >
                    <select
                        name="project_id"
                        required
                        x-model="selectedId"
                        @change="onProjectChange($event.target.value)"
                        class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                    >
                        <option value="">Selecciona un proyecto</option>
                        <template x-for="item in filteredProjects()" :key="item.id">
                            <option :value="String(item.id)" x-text="item.code + ' — ' + item.name" :selected="String(item.id) === String(selectedId)"></option>
                        </template>
                        <option value="__new__">+ Crear nuevo proyecto</option>
                    </select>
                </div>

                <div
                    x-show="showNewProject"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
                >
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg" @click.outside="cancelNewProject()">
                        <h2 class="text-lg font-semibold">Nuevo proyecto</h2>
                        <p class="mt-1 text-sm text-slate-500">Se usa la misma alta del módulo Proyectos.</p>
                        <label class="mt-4 block text-sm font-medium text-slate-700">Nombre del proyecto</label>
                        <input type="text" x-model="newProjectName" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm" placeholder="Ej: Residencial Los Pinos">
                        <p class="mt-2 text-sm text-red-600" x-show="newProjectError" x-text="newProjectError"></p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" @click="cancelNewProject()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">Cancelar</button>
                            <button type="button" @click="saveNewProject()" :disabled="savingProject" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                                Guardar proyecto
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <label class="block text-sm font-medium text-slate-700">Descripción del trabajo</label>
                <textarea name="work_description" rows="4" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">{{ old('work_description', $quotation?->workDescription()) }}</textarea>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Productos / servicios</h2>
                    <button type="button" @click="addLine()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium hover:bg-slate-50">Agregar línea</button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-4 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <label class="text-xs font-medium text-slate-500">Producto</label>
                                <input type="text" :name="`lines[${index}][product_name]`" x-model="line.product_name" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500">Cantidad</label>
                                <input type="number" step="0.01" min="0.01" :name="`lines[${index}][quantity]`" x-model="line.quantity" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500">Marca</label>
                                <input type="text" :name="`lines[${index}][brand]`" x-model="line.brand" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500">Serie</label>
                                <input type="text" :name="`lines[${index}][serial]`" x-model="line.serial" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500">Precio unitario</label>
                                <div class="mt-1 flex gap-2">
                                    <input type="number" step="0.01" min="0" :name="`lines[${index}][unit_price]`" x-model="line.unit_price" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <button type="button" @click="removeLine(index)" class="rounded-lg px-2 text-red-600 hover:bg-red-50" x-show="lines.length > 1">×</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ $cancelUrl }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium">Cancelar</a>
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Guardar</button>
            </div>
        </form>
    </div>

    <script>
        function quotationForm(config) {
            return {
                lines: config.lines,
                projects: config.projects,
                selectedId: config.selectedId || '',
                projectQuery: '',
                showNewProject: false,
                newProjectName: '',
                newProjectError: '',
                savingProject: false,
                previousId: config.selectedId || '',
                addLine() {
                    this.lines.push({ product_name: '', quantity: '1', brand: '', serial: '', unit_price: '0' });
                },
                removeLine(index) {
                    if (this.lines.length > 1) this.lines.splice(index, 1);
                },
                filteredProjects() {
                    const q = (this.projectQuery || '').toLowerCase();
                    let list = this.projects;
                    if (q) {
                        list = this.projects.filter((item) =>
                            (item.name + ' ' + item.code).toLowerCase().includes(q)
                        );
                    }
                    const selected = this.projects.find((item) => String(item.id) === String(this.selectedId));
                    if (selected && !list.some((item) => item.id === selected.id)) {
                        list = [selected, ...list];
                    }
                    return list;
                },
                onProjectChange(value) {
                    if (value === '__new__') {
                        this.showNewProject = true;
                        this.selectedId = this.previousId;
                        this.newProjectError = '';
                        return;
                    }
                    this.previousId = value;
                    this.selectedId = value;
                },
                cancelNewProject() {
                    this.showNewProject = false;
                    this.newProjectName = '';
                    this.newProjectError = '';
                },
                async saveNewProject() {
                    const name = (this.newProjectName || '').trim();
                    if (!name) {
                        this.newProjectError = 'El nombre del proyecto es obligatorio.';
                        return;
                    }
                    this.savingProject = true;
                    this.newProjectError = '';
                    try {
                        const response = await fetch(config.createUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ name }),
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            this.newProjectError = data.message || Object.values(data.errors || {}).flat().join(' ') || 'No se pudo crear el proyecto.';
                            return;
                        }
                        this.projects.push({ id: data.id, name: data.name, code: data.code });
                        this.selectedId = String(data.id);
                        this.previousId = this.selectedId;
                        this.projectQuery = '';
                        this.cancelNewProject();
                    } catch (error) {
                        this.newProjectError = 'No se pudo crear el proyecto.';
                    } finally {
                        this.savingProject = false;
                    }
                },
            };
        }
    </script>
</x-layout>
