@props([
    'projects' => [],
    'technicians' => [],
    'selectedProjectId' => null,
    'order' => null,
])

<div class="space-y-6">
    <div>
        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Trabajo</h3>
        <div class="mt-3 space-y-4">
            <x-ui.form-field label="Proyecto" for="project_id" required>
                <x-ui.select id="project_id" name="project_id" required class="min-h-11 w-full">
                    <option value="">Selecciona un proyecto</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((int) old('project_id', $selectedProjectId) === (int) $project->id)>
                            {{ $project->code }} — {{ $project->name }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>

            <x-ui.form-field label="Descripción del trabajo" for="description" required :error="$errors->first('description')">
                <x-ui.textarea id="description" name="description" rows="4" required class="w-full">{{ old('description') }}</x-ui.textarea>
            </x-ui.form-field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.form-field label="Prioridad" for="priority" required>
                    <x-ui.select id="priority" name="priority" required class="min-h-11 w-full">
                        @foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'media') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.form-field label="Fecha programada" for="scheduled_at">
                    <x-ui.input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="min-h-11 w-full" />
                </x-ui.form-field>
            </div>

            <x-ui.form-field label="Técnico asignado" for="staff_id" hint="Puedes dejarlo vacío y asignar después.">
                <x-ui.select id="staff_id" name="staff_id" class="min-h-11 w-full">
                    <option value="">Sin asignar</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected((string) old('staff_id') === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>
        </div>
    </div>

    <div class="border-t border-border-subtle pt-5">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Solicitante</h3>
        <div class="mt-3 grid gap-4">
            <x-ui.form-field label="Nombre del solicitante" for="requester_name" required :error="$errors->first('requester_name')">
                <x-ui.input id="requester_name" type="text" name="requester_name" value="{{ old('requester_name') }}" required class="min-h-11 w-full" />
            </x-ui.form-field>
            <x-ui.form-field label="Teléfono del solicitante" for="requester_phone" required :error="$errors->first('requester_phone')">
                <x-ui.input id="requester_phone" type="text" name="requester_phone" value="{{ old('requester_phone') }}" required class="min-h-11 w-full" />
            </x-ui.form-field>
        </div>
    </div>

    <div class="border-t border-border-subtle pt-5">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Observaciones</h3>
        <x-ui.form-field label="Observaciones" for="observations" class="mt-3">
            <x-ui.textarea id="observations" name="observations" rows="3" class="w-full">{{ old('observations') }}</x-ui.textarea>
        </x-ui.form-field>
    </div>
</div>
