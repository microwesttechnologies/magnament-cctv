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
    <x-ui.page-header
        :title="$quotation ? 'Editar cotización' : 'Nueva cotización'"
        :description="$standalone ? 'Selecciona un proyecto y completa los datos comerciales.' : 'Proyecto: '.$project->name.' ('.$project->code.')'"
    />

    @if ($errors->any())
        <x-ui.alert variant="error" class="mb-6">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <form
        method="POST"
        action="{{ $formAction }}"
        class="w-full space-y-6"
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
            <x-ui.card title="Proyecto">
                <x-ui.form-field label="Buscar proyecto" hint="Busca un proyecto existente o crea uno nuevo desde el selector.">
                    <x-ui.search x-model="projectQuery" placeholder="Buscar proyecto…" />
                </x-ui.form-field>
                <x-ui.form-field label="Proyecto" required class="mt-4">
                    <x-ui.select
                        name="project_id"
                        required
                        x-model="selectedId"
                        @change="onProjectChange($event.target.value)"
                    >
                        <option value="">Selecciona un proyecto</option>
                        <template x-for="item in filteredProjects()" :key="item.id">
                            <option :value="String(item.id)" x-text="item.code + ' — ' + item.name" :selected="String(item.id) === String(selectedId)"></option>
                        </template>
                        <option value="__new__">+ Crear nuevo proyecto</option>
                    </x-ui.select>
                </x-ui.form-field>
            </x-ui.card>

            <div
                x-show="showNewProject"
                x-cloak
                class="fixed inset-0 z-[70] flex items-center justify-center bg-primary/40 p-4"
                role="dialog"
                aria-modal="true"
            >
                <div class="w-full max-w-md rounded-lg border border-border bg-surface p-6 shadow-lg" @click.outside="cancelNewProject()">
                    <h2 class="text-lg font-semibold text-foreground">Nuevo proyecto</h2>
                    <p class="mt-1 text-sm text-foreground-muted">Se usa la misma alta del módulo Proyectos.</p>
                    <x-ui.form-field label="Nombre del proyecto" class="mt-4">
                        <x-ui.input type="text" x-model="newProjectName" placeholder="Ej: Residencial Los Pinos" />
                    </x-ui.form-field>
                    <p class="mt-2 text-sm text-destructive" x-show="newProjectError" x-text="newProjectError" role="alert"></p>
                    <div class="mt-5 flex justify-end gap-2">
                        <x-ui.button type="button" variant="outline" @click="cancelNewProject()">Cancelar</x-ui.button>
                        <x-ui.button type="button" @click="saveNewProject()" x-bind:disabled="savingProject">Guardar proyecto</x-ui.button>
                    </div>
                </div>
            </div>
        @endif

        <x-ui.card title="Descripción del trabajo">
            <x-ui.form-field label="Descripción" required>
                <x-ui.textarea name="work_description" rows="4" required>{{ old('work_description', $quotation?->workDescription()) }}</x-ui.textarea>
            </x-ui.form-field>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div class="flex w-full items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-foreground">Productos / servicios</h2>
                    <x-ui.button type="button" variant="outline" size="sm" @click="addLine()">Agregar línea</x-ui.button>
                </div>
            </x-slot:header>

            <div class="space-y-4">
                <template x-for="(line, index) in lines" :key="index">
                    <div class="grid gap-3 rounded-lg border border-border-subtle bg-background p-4 sm:grid-cols-6">
                        <x-ui.form-field label="Producto" class="sm:col-span-2">
                            <input type="text" :name="`lines[${index}][product_name]`" x-model="line.product_name" required class="ui-input-base">
                        </x-ui.form-field>
                        <x-ui.form-field label="Cantidad">
                            <input type="number" step="0.01" min="0.01" :name="`lines[${index}][quantity]`" x-model="line.quantity" required class="ui-input-base">
                        </x-ui.form-field>
                        <x-ui.form-field label="Marca">
                            <input type="text" :name="`lines[${index}][brand]`" x-model="line.brand" class="ui-input-base">
                        </x-ui.form-field>
                        <x-ui.form-field label="Serie">
                            <input type="text" :name="`lines[${index}][serial]`" x-model="line.serial" class="ui-input-base">
                        </x-ui.form-field>
                        <x-ui.form-field label="Precio unitario">
                            <div class="flex gap-2">
                                <input type="number" step="0.01" min="0" :name="`lines[${index}][unit_price]`" x-model="line.unit_price" required class="ui-input-base w-full">
                                <x-ui.icon-button type="button" variant="ghost" @click="removeLine(index)" x-show="lines.length > 1" aria-label="Eliminar línea">
                                    <span class="text-destructive">×</span>
                                </x-ui.icon-button>
                            </div>
                        </x-ui.form-field>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.button variant="outline" :href="$cancelUrl">Cancelar</x-ui.button>
            <x-ui.button type="submit">Guardar</x-ui.button>
        </div>
    </form>

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
