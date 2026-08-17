<x-layout title="Nueva orden de servicio · CCTV Manager" active="ordenes">
    <x-ui.page-header
        title="Nueva orden de servicio"
        description="Asigna un trabajo técnico a un proyecto. Si eliges técnico, queda asignada de inmediato."
    />

    @if ($errors->any())
        <x-ui.alert variant="error" class="mb-6">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('service-orders.store') }}" class="w-full max-w-3xl space-y-6">
        @csrf
        <x-ui.card title="Trabajo">
            <x-ui.form-field label="Proyecto" for="project_id" required>
                <x-ui.select id="project_id" name="project_id" required>
                    <option value="">Selecciona un proyecto</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((int) old('project_id', $selectedProjectId) === (int) $project->id)>
                            {{ $project->code }} — {{ $project->name }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>

            <x-ui.form-field label="Descripción del trabajo" for="description" required class="mt-4" :error="$errors->first('description')">
                <x-ui.textarea id="description" name="description" rows="4" required>{{ old('description') }}</x-ui.textarea>
            </x-ui.form-field>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.form-field label="Prioridad" for="priority" required>
                    <x-ui.select id="priority" name="priority" required>
                        @foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'media') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>
                <x-ui.form-field label="Fecha programada" for="scheduled_at">
                    <x-ui.input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" />
                </x-ui.form-field>
            </div>

            <x-ui.form-field label="Técnico asignado" for="staff_id" class="mt-4" hint="Solo personal con rol técnico activo. Puedes dejarlo vacío y asignar después.">
                <x-ui.select id="staff_id" name="staff_id">
                    <option value="">Sin asignar</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected((string) old('staff_id') === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>

            <x-ui.form-field label="Observaciones" for="observations" class="mt-4">
                <x-ui.textarea id="observations" name="observations" rows="3">{{ old('observations') }}</x-ui.textarea>
            </x-ui.form-field>
        </x-ui.card>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">Crear orden</x-ui.button>
            <x-ui.button variant="ghost" href="{{ route('service-orders.index') }}">Cancelar</x-ui.button>
        </div>
    </form>
</x-layout>
