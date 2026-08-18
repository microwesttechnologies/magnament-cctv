@php
    $initialLines = old('lines');
    if (! is_array($initialLines)) {
        $initialLines = [['product_name' => '', 'quantity' => '1', 'brand' => '', 'serial' => '', 'unit_price' => '0']];
    }
@endphp

<form
    id="quotation-create-form"
    method="POST"
    action="{{ route('quotations.store') }}"
    class="space-y-6"
    data-create-modal-form
    x-data="quotationForm({
        lines: @js($initialLines),
        projects: @js($projects->map(fn ($p) => ['id' => (int) $p->id, 'name' => $p->name, 'code' => $p->code])->values()),
        selectedId: @js((string) old('project_id', '')),
        createUrl: @js(route('quotations.projects.store')),
        csrf: @js(csrf_token()),
        standalone: true,
    })"
>
    @csrf
    <input type="hidden" name="_form" value="quotation-create">
    <input type="hidden" name="project_id" :value="projectIdForSubmit()">

    <x-ui.card title="Proyecto">
        <x-ui.form-field label="Buscar proyecto" hint="Busca un proyecto existente o crea uno nuevo desde el selector.">
            <x-ui.search x-model="projectQuery" placeholder="Buscar proyecto…" />
        </x-ui.form-field>
        <x-ui.form-field label="Proyecto" required class="mt-4">
            <x-ui.select
                required
                x-model="selectedId"
                @change="onProjectChange($event.target.value)"
                class="min-h-11 w-full"
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
        class="fixed inset-0 z-[85] flex items-center justify-center bg-primary/40 p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-md rounded-lg border border-border bg-surface p-6 shadow-lg" @click.stop>
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

    <x-ui.card title="Solicitud">
        <p class="mb-4 text-sm text-foreground-muted">¿Qué necesita el cliente?</p>
        <div x-data="{ value: {{ \Illuminate\Support\Js::from(old('work_description', '')) }}, max: 10000 }">
            <x-ui.form-field label="Solicitud" for="work_description" required :error="$errors->first('work_description')">
                <x-ui.textarea id="work_description" name="work_description" rows="5" required maxlength="10000" x-model="value" class="min-h-[7rem] w-full" />
                <p class="mt-1.5 text-xs text-foreground-muted"><span x-text="value.length"></span> / <span x-text="max"></span> caracteres</p>
            </x-ui.form-field>
        </div>
    </x-ui.card>

    <x-ui.card title="Solución diseñada">
        <div x-data="{ value: {{ \Illuminate\Support\Js::from(old('designed_solution', '')) }}, max: 10000 }">
            <x-ui.form-field label="Solución diseñada" for="designed_solution" :error="$errors->first('designed_solution')">
                <x-ui.textarea id="designed_solution" name="designed_solution" rows="5" maxlength="10000" x-model="value" class="min-h-[7rem] w-full" />
                <p class="mt-1.5 text-xs text-foreground-muted"><span x-text="value.length"></span> / <span x-text="max"></span> caracteres</p>
            </x-ui.form-field>
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-foreground">Propuesta económica</h2>
                    <p class="mt-1 text-sm text-foreground-muted">Producto, cantidad, valor unitario y valor subtotal.</p>
                </div>
                <x-ui.button type="button" variant="outline" size="sm" class="min-h-11" @click="addLine()">Agregar línea</x-ui.button>
            </div>
        </x-slot:header>
        <div class="space-y-4">
            <template x-for="(line, index) in lines" :key="index">
                <div class="rounded-lg border border-border-subtle bg-background p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                        <x-ui.form-field label="Producto" class="lg:col-span-5">
                            <input type="text" :name="`lines[${index}][product_name]`" x-model="line.product_name" required class="ui-input-base min-h-11 w-full">
                        </x-ui.form-field>
                        <x-ui.form-field label="Cantidad" class="lg:col-span-2">
                            <input type="number" step="0.01" min="0.01" :name="`lines[${index}][quantity]`" x-model="line.quantity" required class="ui-input-base min-h-11 w-full">
                        </x-ui.form-field>
                        <x-ui.form-field label="Valor unitario" class="lg:col-span-2">
                            <input type="number" step="0.01" min="0" :name="`lines[${index}][unit_price]`" x-model="line.unit_price" required class="ui-input-base min-h-11 w-full">
                        </x-ui.form-field>
                        <x-ui.form-field label="Valor subtotal" class="lg:col-span-3">
                            <div class="flex gap-2">
                                <input type="text" :value="lineSubtotal(line)" readonly class="ui-input-base min-h-11 w-full bg-muted font-mono">
                                <x-ui.icon-button type="button" variant="ghost" class="min-h-11 min-w-11" @click="removeLine(index)" x-show="lines.length > 1" aria-label="Eliminar línea">
                                    <span class="text-destructive">×</span>
                                </x-ui.icon-button>
                            </div>
                        </x-ui.form-field>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>
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
            projectIdForSubmit() {
                const id = String(this.selectedId ?? '').trim();
                if (id === '' || id === '__new__') {
                    return '';
                }
                return id;
            },
            addLine() {
                this.lines.push({ product_name: '', quantity: '1', brand: '', serial: '', unit_price: '0' });
            },
            removeLine(index) {
                if (this.lines.length > 1) this.lines.splice(index, 1);
            },
            lineSubtotal(line) {
                const quantity = Number.parseFloat(line.quantity);
                const unitPrice = Number.parseFloat(line.unit_price);
                if (!Number.isFinite(quantity) || !Number.isFinite(unitPrice)) return '0.00';
                return (quantity * unitPrice).toFixed(2);
            },
            filteredProjects() {
                const q = (this.projectQuery || '').toLowerCase();
                let list = this.projects;
                if (q) {
                    list = this.projects.filter((item) => (item.name + ' ' + item.code).toLowerCase().includes(q));
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
                            Accept: 'application/json',
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
                } catch {
                    this.newProjectError = 'No se pudo crear el proyecto.';
                } finally {
                    this.savingProject = false;
                }
            },
        };
    }
</script>
