@php
    $statusStyles = [
        'activo' => ['label' => 'Activo', 'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700'],
        'instalacion' => ['label' => 'En Instalación', 'dot' => 'bg-blue-500', 'badge' => 'bg-blue-50 text-blue-700'],
        'mantenimiento' => ['label' => 'Mantenimiento', 'dot' => 'bg-orange-500', 'badge' => 'bg-orange-50 text-orange-700'],
        'borrador' => ['label' => 'Borrador', 'dot' => 'bg-slate-400', 'badge' => 'bg-slate-100 text-slate-600'],
    ];
@endphp

<x-layout title="Proyectos · CCTV Manager" active="proyectos">
    <div x-data="projectForm({{ $errors->any() && ! $errors->has('confirmation') ? 'true' : 'false' }})">
        <x-ui.page-header title="Gestión de Proyectos" description="Supervisión y control técnico de despliegues IP.">
            <x-slot:actions>
                <x-ui.button type="button" @click="open = true">Crear nuevo proyecto</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat-card label="Proyectos activos" :value="str_pad((string) $stats['activos'], 2, '0', STR_PAD_LEFT)" />
            <x-ui.stat-card label="En instalación" :value="str_pad((string) $stats['instalacion'], 2, '0', STR_PAD_LEFT)" />
            <x-ui.stat-card label="Mantenimiento" :value="str_pad((string) $stats['mantenimiento'], 2, '0', STR_PAD_LEFT)" />
            <x-ui.stat-card label="Total cámaras" :value="number_format($stats['camaras'])" />
        </div>

        <x-ui.card class="mt-6" :padding="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
                <div class="relative min-w-56 flex-1">
                    <x-ui.input type="text" placeholder="Buscar por nombre o ubicación" class="pl-9" />
                </div>
            </div>

            <div class="w-full min-w-0 overflow-x-auto">
                <table class="ui-table">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <th class="w-[28%] px-5 py-3">Nombre del Proyecto</th>
                            <th class="w-[28%] px-5 py-3">Ubicación</th>
                            <th class="w-[14%] px-5 py-3">Estado</th>
                            <th class="w-[16%] px-5 py-3">Cámaras / Equipos</th>
                            <th class="w-[14%] px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($projects as $project)
                            @php $s = $statusStyles[$project->status] ?? $statusStyles['borrador']; @endphp
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/80">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ $project->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">ID: {{ $project->code ?? 'N/A' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-slate-700">{{ $project->address ?: 'Sin dirección' }}{{ $project->city ? ', '.$project->city : '' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $project->neighborhood ?: $project->type }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $s['badge'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>
                                        {{ $s['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-mono text-sm text-slate-700">{{ (int) ($project->dvrs_sum_ports ?? 0) }} Canales</p>
                                    <p class="mt-0.5 font-mono text-xs text-slate-400">{{ $project->dvrs_count }} DVR/NVR</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('projects.show', $project) }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-600" aria-label="Ver">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Editar">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="askDelete({{ $project->id }}, @js($project->name))" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Eliminar">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                                    No hay proyectos registrados todavía. Crea el primero con “Crear Nuevo Proyecto”.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border p-4">
                <p class="text-sm text-foreground-muted">Mostrando {{ $projects->count() }} proyecto(s)</p>
            </div>
        </x-ui.card>

        {{-- ============ MODAL CREAR PROYECTO ============ --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-8" @keydown.escape.window="open = false">
            <div class="my-4 w-full max-w-2xl rounded-2xl bg-white shadow-xl" @click.outside="open = false">
                <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Cabecera --}}
                    <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Crear Proyecto</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Ingresa los datos técnicos de la nueva instalación.</p>
                        </div>
                        <button type="button" @click="open = false" class="text-slate-400 transition hover:text-slate-600" aria-label="Cerrar">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-5">
                        {{-- Nombre + Tipo --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Nombre del Proyecto</label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: Condominio Vista Hermosa" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Tipo de Proyecto</label>
                                <select name="type" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                    @foreach (['Residencial', 'Comercial', 'Industrial', 'Corporativo'] as $t)
                                        <option value="{{ $t }}" @selected(old('type') === $t)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Dirección --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Dirección Exacta</label>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Calle, Carrera, Edificio, Oficina" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                        </div>

                        {{-- Barrio + Ciudad --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Barrio</label>
                                <input type="text" name="neighborhood" value="{{ old('neighborhood') }}" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Ciudad</label>
                                <input type="text" name="city" value="{{ old('city') }}" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                            </div>
                        </div>

                        {{-- Hojas de plano --}}
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium text-slate-700">Hojas del Plano</label>
                                <button type="button" @click="addSheet()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Agregar hoja
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Puedes subir varias hojas (pisos). Formatos: PNG, JPG, PDF.</p>

                            <div class="mt-2 space-y-2">
                                <template x-for="(sheet, i) in sheets" :key="sheet.key">
                                    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <input type="text" :name="'floor_plan_names['+i+']'" x-model="sheet.name" :placeholder="'Piso ' + (i + 1)" class="w-full min-w-32 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none sm:max-w-[10rem]">
                                        <label class="inline-flex flex-1 cursor-pointer items-center justify-between gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 transition hover:border-blue-400 hover:bg-blue-50/40">
                                            <span class="truncate" x-text="sheet.fileName || 'Seleccionar archivo…'"></span>
                                            <span class="shrink-0 text-xs font-semibold text-blue-600">Examinar</span>
                                            <input type="file" :name="'floor_plans['+i+']'" accept=".png,.jpg,.jpeg,.pdf" class="hidden" @change="onSheetFile($event, i)">
                                        </label>
                                        <button type="button" @click="removeSheet(i)" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Quitar hoja">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </template>
                                <p x-show="sheets.length === 0" class="rounded-lg border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-400">Sin hojas. Usa “Agregar hoja” o deja vacío si aún no tienes el plano.</p>
                            </div>
                            @error('floor_plans.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('floor_plans') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Configuración de equipos (colapsable) --}}
                        <div class="rounded-lg border border-slate-200">
                            <button type="button" @click="showDvrConfig = !showDvrConfig" class="flex w-full items-center justify-between px-4 py-3 text-left">
                                <span class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-700">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h7.5a2.25 2.25 0 002.25-2.25V7.5A2.25 2.25 0 0012 5.25H4.5A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    Configuración de Equipos (DVR/NVR)
                                </span>
                                <svg class="h-5 w-5 text-slate-400 transition" :class="showDvrConfig && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-show="showDvrConfig" x-cloak class="border-t border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600">Marca</label>
                                        <input type="text" x-model="newDvr.brand" placeholder="Hikvision, Dahua..." class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600">Serie / Modelo</label>
                                        <input type="text" x-model="newDvr.serial_model" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600">Número de Puertos</label>
                                        <select x-model.number="newDvr.ports" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                            <option value="4">4 Ch</option>
                                            <option value="8">8 Ch</option>
                                            <option value="16">16 Ch</option>
                                            <option value="32">32 Ch</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600">Número de Discos</label>
                                        <input type="number" min="0" x-model.number="newDvr.disks" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600">Dirección IP</label>
                                        <input type="text" x-model="newDvr.ip_address" placeholder="192.168.1.100" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600">Ubicación Física</label>
                                        <input type="text" x-model="newDvr.physical_location" placeholder="Rack Piso 2" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none">
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end gap-2">
                                    <button type="button" @click="showDvrConfig = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancelar</button>
                                    <button type="button" @click="addDvr()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Guardar DVR</button>
                                </div>
                            </div>
                        </div>

                        {{-- Equipos en este proyecto --}}
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Equipos en este proyecto</p>
                            <div class="mt-2 overflow-hidden rounded-lg border border-slate-200">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            <th class="px-4 py-2">Serie</th>
                                            <th class="px-4 py-2">IP</th>
                                            <th class="px-4 py-2">Puertos</th>
                                            <th class="px-4 py-2 text-right">Borrar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-if="dvrs.length === 0">
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-sm italic text-slate-400">No hay DVRs agregados todavía.</td>
                                            </tr>
                                        </template>
                                        <template x-for="(dvr, i) in dvrs" :key="i">
                                            <tr>
                                                <td class="px-4 py-2">
                                                    <span class="font-medium text-slate-800" x-text="dvr.brand + ' ' + dvr.serial_model"></span>
                                                </td>
                                                <td class="px-4 py-2 font-mono text-slate-600" x-text="dvr.ip_address || '—'"></td>
                                                <td class="px-4 py-2 font-mono text-slate-600" x-text="dvr.ports + ' Ch'"></td>
                                                <td class="px-4 py-2 text-right">
                                                    <button type="button" @click="removeDvr(i)" class="text-slate-400 transition hover:text-red-600" aria-label="Borrar">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Inputs ocultos con los DVRs para enviar al servidor --}}
                        <template x-for="(dvr, i) in dvrs" :key="'h'+i">
                            <div class="hidden">
                                <input type="hidden" :name="'dvrs['+i+'][brand]'" :value="dvr.brand">
                                <input type="hidden" :name="'dvrs['+i+'][serial_model]'" :value="dvr.serial_model">
                                <input type="hidden" :name="'dvrs['+i+'][ports]'" :value="dvr.ports">
                                <input type="hidden" :name="'dvrs['+i+'][disks]'" :value="dvr.disks">
                                <input type="hidden" :name="'dvrs['+i+'][ip_address]'" :value="dvr.ip_address">
                                <input type="hidden" :name="'dvrs['+i+'][physical_location]'" :value="dvr.physical_location">
                            </div>
                        </template>
                    </div>

                    {{-- Pie --}}
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="submit" name="action" value="draft" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Guardar Borrador</button>
                        <button type="submit" name="action" value="final" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Crear Proyecto Final</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============ MODAL ELIMINAR PROYECTO ============ --}}
        <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="deleteOpen = false">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-xl" @click.outside="deleteOpen = false">
                <form method="POST" x-bind:action="deleteAction" @submit="if (confirmText !== deleteTarget.name) $event.preventDefault()">
                    @csrf
                    @method('DELETE')

                    <div class="px-6 py-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold text-slate-900">Eliminar proyecto</h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    Esta acción es <strong>irreversible</strong>. Se eliminarán en cascada todos los DVRs y equipos asociados a
                                    <span class="font-semibold text-slate-800" x-text="deleteTarget.name"></span>.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-slate-700">
                                Escribe el nombre del proyecto (<span class="font-semibold text-red-600" x-text="deleteTarget.name"></span>) para confirmar
                            </label>
                            <input type="text" name="confirmation" x-model="confirmText" autocomplete="off" placeholder="Nombre del proyecto" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none">
                            @error('confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="deleteOpen = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="confirmText !== deleteTarget.name" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">Eliminar definitivamente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function projectForm(openOnError) {
            return {
                open: openOnError,
                showDvrConfig: false,
                sheets: [{ key: Date.now(), name: 'Piso 1', fileName: '' }],
                sheetKey: Date.now(),
                dvrs: [],
                newDvr: { brand: '', serial_model: '', ports: 4, disks: 1, ip_address: '', physical_location: '' },
                deleteOpen: false,
                deleteTarget: { id: null, name: '' },
                confirmText: '',
                deleteAction: '',
                addSheet() {
                    this.sheetKey += 1;
                    this.sheets.push({ key: this.sheetKey, name: 'Piso ' + (this.sheets.length + 1), fileName: '' });
                },
                removeSheet(i) {
                    this.sheets.splice(i, 1);
                },
                onSheetFile(event, i) {
                    const file = event.target.files?.[0];
                    this.sheets[i].fileName = file ? file.name : '';
                },
                addDvr() {
                    if (!this.newDvr.brand && !this.newDvr.serial_model) return;
                    this.dvrs.push({ ...this.newDvr });
                    this.newDvr = { brand: '', serial_model: '', ports: 4, disks: 1, ip_address: '', physical_location: '' };
                    this.showDvrConfig = false;
                },
                removeDvr(i) {
                    this.dvrs.splice(i, 1);
                },
                askDelete(id, name) {
                    this.deleteTarget = { id, name };
                    this.confirmText = '';
                    this.deleteAction = '{{ url('projects') }}/' + id;
                    this.deleteOpen = true;
                },
            };
        }
    </script>
</x-layout>
