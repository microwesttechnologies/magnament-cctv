@php
    $statusStyles = [
        'activo' => ['label' => 'Activo', 'badge' => 'bg-emerald-50 text-emerald-700'],
        'instalacion' => ['label' => 'En Instalación', 'badge' => 'bg-blue-50 text-blue-700'],
        'mantenimiento' => ['label' => 'Mantenimiento', 'badge' => 'bg-orange-50 text-orange-700'],
        'borrador' => ['label' => 'Borrador', 'badge' => 'bg-slate-100 text-slate-600'],
    ];
    $s = $statusStyles[$project->status] ?? $statusStyles['borrador'];
    $sheets = $project->floorPlans->map(fn ($fp) => [
        'id' => $fp->id,
        'name' => $fp->name ?: 'Hoja '.$fp->id,
        'url' => $fp->url(),
        'isImage' => $fp->isImage(),
        'deleteUrl' => route('projects.floor-plans.destroy', [$project, $fp]),
        'cameras' => $fp->cameras->map(fn ($cam) => [
            'id' => $cam->id,
            'name' => $cam->name,
            'description' => $cam->description,
            'brand' => $cam->brand,
            'reference' => $cam->reference,
            'serial' => $cam->serial,
            'channel' => $cam->channel,
            'dvr_id' => $cam->dvr_id,
            'dvr_label' => trim(($cam->dvr?->brand ?? '').' '.($cam->dvr?->serial_model ?? '')) ?: 'DVR #'.$cam->dvr_id,
            'photo_url' => $cam->photoUrl(),
            'pos_x' => (float) $cam->pos_x,
            'pos_y' => (float) $cam->pos_y,
            'update_url' => route('projects.cameras.update', [$project, $cam]),
            'delete_url' => route('projects.cameras.destroy', [$project, $cam]),
        ])->values(),
    ])->values();
    $dvrsPayload = $project->dvrs->map(fn ($dvr) => [
        'id' => $dvr->id,
        'label' => trim(($dvr->brand ?? '').' '.($dvr->serial_model ?? '')) ?: 'DVR #'.$dvr->id,
        'ports' => (int) $dvr->ports,
        'used' => ($usedChannelsByDvr[$dvr->id] ?? collect())->map(fn ($c) => (int) $c)->values()->all(),
    ])->values();
@endphp

<x-layout :title="$project->name.' · CCTV Manager'" active="proyectos">
    @php
        $flashToast = [
            'success' => session('status'),
            'error' => $errors->any() ? $errors->first() : null,
        ];
    @endphp

    <a href="{{ route('projects') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Volver a la lista
    </a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-bold tracking-tight">{{ $project->name }}</h1>
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {{ $s['badge'] }}">{{ $s['label'] }}</span>
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $project->type }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-400">ID Proyecto: {{ $project->code ?? 'N/A' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.quotations.create', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Nueva cotización
            </a>
            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Generar Reporte PDF
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Editar Proyecto
            </button>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cotizaciones</h2>
            <a href="{{ route('cotizaciones') }}" class="text-sm text-blue-600 hover:underline">Ver todas</a>
        </div>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @forelse ($project->quotations as $quote)
                <li class="flex items-center justify-between gap-3 py-2">
                    <div>
                        <a class="font-medium text-slate-900 hover:underline" href="{{ route('projects.quotations.show', [$project, $quote->id]) }}">{{ $quote->code }}</a>
                        <span class="ml-2 capitalize text-slate-500">{{ $quote->status }}</span>
                    </div>
                    <span class="text-slate-600">{{ $quote->total }}</span>
                </li>
            @empty
                <li class="py-3 text-slate-500">Sin cotizaciones. Crea la primera desde “Nueva cotización”.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-900 text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008zm-3 0h.008v.008h-.008v-.008z" /></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Total DVRs</p>
                <p class="text-2xl font-bold text-slate-900">{{ str_pad((string) $project->dvrs->count(), 2, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-500 text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h7.5a2.25 2.25 0 002.25-2.25V7.5A2.25 2.25 0 0012 5.25H4.5A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Total Cámaras</p>
                <p class="text-2xl font-bold text-slate-900">{{ $totalCameras }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-500 text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Uptime</p>
                <p class="text-2xl font-bold text-slate-900">99.8%</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-500 text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" /></svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Último Mantenimiento</p>
                <p class="text-2xl font-bold text-slate-900">{{ optional($project->updated_at)->format('d/m/y') ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Información General</h2>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Dirección</p>
                    <p class="mt-0.5 text-sm text-slate-800">{{ $project->address ?: 'Sin dirección' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Barrio</p>
                    <p class="mt-0.5 text-sm text-slate-800">{{ $project->neighborhood ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Ciudad</p>
                    <p class="mt-0.5 text-sm text-slate-800">{{ $project->city ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Fecha de Instalación</p>
                    <p class="mt-0.5 text-sm text-slate-800">{{ optional($project->created_at)->translatedFormat('d \d\e F, Y') ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-3"
            x-data="planViewer({{ $project->id }}, {{ \Illuminate\Support\Js::from($sheets) }}, {{ \Illuminate\Support\Js::from($dvrsPayload) }}, {{ session('open_plan_viewer') || $errors->any() ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($flashToast) }})"
        >
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Topología y Distribución</h2>
                <button type="button" @click="showAdd = true" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Agregar hoja
                </button>
            </div>

            <div class="p-5">
                <div x-show="sheets.length > 0" class="mb-3 flex flex-wrap items-center gap-2">
                    <template x-for="(sheet, i) in sheets" :key="sheet.id">
                        <div class="group relative">
                            <button type="button" @click="selectSheet(i)" class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition" :class="activeIndex === i ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'" x-text="sheet.name"></button>
                            <form :action="sheet.deleteUrl" method="POST" class="absolute -right-1.5 -top-1.5 hidden group-hover:block" @submit="if (!confirm('¿Eliminar la hoja «' + sheet.name + '»?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white shadow" title="Eliminar hoja">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </form>
                        </div>
                    </template>
                </div>

                <div class="relative h-80 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                    <template x-if="activeSheet && activeSheet.isImage">
                        <button type="button" @click="openViewer()" class="group block h-full w-full" aria-label="Ampliar plano">
                            <img :src="activeSheet.url" :alt="activeSheet.name" class="h-full w-full cursor-zoom-in object-cover transition duration-300 group-hover:scale-105">
                            <span class="pointer-events-none absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-md bg-slate-900/80 px-2.5 py-1.5 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100">Ver a pantalla completa · clic en el plano para agregar cámaras</span>
                        </button>
                    </template>
                    <template x-if="activeSheet && !activeSheet.isImage">
                        <a :href="activeSheet.url" target="_blank" class="flex h-full w-full flex-col items-center justify-center gap-2 text-slate-500 hover:text-blue-600">
                            <span class="text-sm font-medium" x-text="'Abrir PDF · ' + activeSheet.name"></span>
                        </a>
                    </template>
                    <template x-if="!activeSheet">
                        <div class="flex h-full w-full flex-col items-center justify-center gap-3 text-sm text-slate-400">
                            <span>Sin hojas de plano. Agrega la primera.</span>
                            <button type="button" @click="showAdd = true" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Agregar hoja</button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal agregar hoja --}}
            <div
                x-show="showAdd"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
                @keydown.escape.window="if (showAdd && !saving) showAdd = false"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-white shadow-xl"
                    x-show="showAdd"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                    @click.outside="if (!saving) showAdd = false"
                >
                    <form method="POST" action="{{ route('projects.floor-plans.store', $project) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-bold text-slate-900">Agregar hoja de plano</h3>
                        </div>
                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Nombre de la hoja</label>
                                <input type="text" name="floor_plan_names[]" placeholder="Ej: Piso 2" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Archivo</label>
                                <input type="file" name="floor_plans[]" accept=".png,.jpg,.jpeg,.pdf" required multiple class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button" @click="showAdd = false" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Guardar hoja(s)</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Visor pantalla completa --}}
            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex flex-col bg-slate-950/95"
                @keydown.window="onKey($event)"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-3 text-white">
                    <span class="truncate text-sm font-medium" x-text="'Plano · ' + (activeSheet?.name || '') + ' · {{ $project->name }}'"></span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="prevSheet()" :disabled="sheets.length < 2" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 disabled:opacity-30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        </button>
                        <span class="min-w-16 text-center text-xs tabular-nums" x-text="(activeIndex + 1) + ' / ' + sheets.length"></span>
                        <button type="button" @click="nextSheet()" :disabled="sheets.length < 2" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 disabled:opacity-30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                        </button>
                        <span class="mx-1 h-6 w-px bg-white/15"></span>
                        <button type="button" @click="zoomOut()" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20" title="Alejar">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                        <span class="w-14 text-center text-sm font-semibold tabular-nums" x-text="Math.round(scale * 100) + '%'"></span>
                        <button type="button" @click="zoomIn()" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20" title="Acercar">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                        <span class="mx-1 h-6 w-px bg-white/15"></span>
                        <button type="button" @click="rotateCCW()" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg></button>
                        <button type="button" @click="rotateCW()" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 100 12h3" /></svg></button>
                        <span class="mx-1 h-6 w-px bg-white/15"></span>
                        <button type="button" @click="reset()" class="flex h-9 items-center rounded-lg bg-white/10 px-3 text-sm font-medium hover:bg-white/20">Reset</button>
                        <button type="button" @click="closeViewer()" class="ml-1 flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 hover:bg-white/20"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>

                <div class="relative flex flex-1 items-center justify-center overflow-auto p-6">
                    <template x-if="activeSheet && activeSheet.isImage">
                        <div class="relative inline-block origin-center transition-transform duration-150 ease-out" :style="`transform: rotate(${rotation}deg) scale(${scale});`">
                            <img
                                x-ref="planImg"
                                :src="activeSheet.url"
                                :alt="activeSheet.name"
                                draggable="false"
                                class="max-h-[82vh] w-auto max-w-[90vw] cursor-crosshair select-none"
                                @click="onPlanClick($event)"
                            >
                            {{-- Marcadores guardados --}}
                            <template x-for="cam in activeCameras" :key="cam.id">
                                <div
                                    class="absolute z-10"
                                    :style="`left:${cam.pos_x}%; top:${cam.pos_y}%; transform: translate(-50%, -50%);`"
                                    @mouseenter="showHover(cam)"
                                    @mouseleave="hideHover()"
                                >
                                    <button
                                        type="button"
                                        data-marker="1"
                                        @click.stop="openEdit(cam)"
                                        class="flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-red-500 shadow ring-2 ring-red-500/40 transition hover:scale-125"
                                        :title="cam.name"
                                    ></button>
                                    {{-- Tooltip hover --}}
                                    <div
                                        x-show="hoverCam && hoverCam.id === cam.id"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 translate-x-1"
                                        x-transition:enter-end="opacity-100 translate-x-0"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="absolute left-5 top-1/2 z-20 flex min-w-48 -translate-y-1/2 items-center gap-2.5 rounded-lg bg-slate-900 px-2.5 py-2 shadow-xl"
                                        @mouseenter="showHover(cam)"
                                        @mouseleave="hideHover()"
                                    >
                                        <button
                                            type="button"
                                            class="h-10 w-10 shrink-0 overflow-hidden rounded bg-slate-700 ring-0 transition hover:ring-2 hover:ring-cyan-400"
                                            @click.stop="if (cam.photo_url) openPhotoLightbox(cam.photo_url)"
                                            :title="cam.photo_url ? 'Ampliar foto' : 'Sin foto'"
                                        >
                                            <template x-if="cam.photo_url">
                                                <img :src="cam.photo_url" alt="" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!cam.photo_url">
                                                <div class="flex h-full w-full items-center justify-center text-[10px] text-slate-400">N/A</div>
                                            </template>
                                        </button>
                                        <div class="min-w-0 text-left">
                                            <p class="truncate text-sm font-semibold text-white" x-text="cam.name"></p>
                                            <p class="truncate text-xs text-cyan-300" x-text="[cam.brand, cam.reference, cam.serial].filter(Boolean).join(' · ') || 'Sin marca'"></p>
                                            <p class="text-[10px] text-slate-300" x-text="cam.dvr_label + ' · Ch ' + cam.channel"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            {{-- Punto temporal --}}
                            <div
                                x-show="tempPoint"
                                x-cloak
                                class="absolute z-10"
                                :style="tempPoint ? `left:${tempPoint.x}%; top:${tempPoint.y}%; transform: translate(-50%, -50%);` : ''"
                            >
                                <span class="block h-4 w-4 animate-pulse rounded-full border-2 border-white bg-blue-500 shadow"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="pb-3 text-center text-xs text-white/50">Clic en el plano para agregar una cámara · Clic en un punto para editarla</p>
            </div>

            {{-- Modal crear / editar cámara --}}
            <div
                x-show="formOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4"
                @keydown.escape.window="if (formOpen && !photoLightbox && !saving) cancelForm()"
            >
                <div
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl"
                    x-show="formOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                    @click.outside="if (!saving && !photoLightbox) cancelForm()"
                >
                    <form method="POST" :action="formMode === 'edit' ? formCamera.update_url : '{{ route('projects.cameras.store', $project) }}'" enctype="multipart/form-data" @submit="onFormSubmit($event)">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" x-bind:disabled="formMode !== 'edit'">
                        <input type="hidden" name="floor_plan_id" :value="activeSheet?.id">
                        <input type="hidden" name="pos_x" :value="formPos.x">
                        <input type="hidden" name="pos_y" :value="formPos.y">

                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-bold text-slate-900" x-text="formMode === 'edit' ? 'Vista previa / Editar cámara' : 'Nueva cámara'"></h3>
                            <p class="mt-0.5 text-sm text-slate-500" x-text="formMode === 'edit' ? 'Actualiza los datos de la cámara en el plano.' : 'Completa los datos para fijar el punto en el plano.'"></p>
                        </div>

                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Nombre</label>
                                <input type="text" name="name" x-model="form.name" required :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Descripción</label>
                                <textarea name="description" x-model="form.description" rows="2" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Marca</label>
                                    <input type="text" name="brand" x-model="form.brand" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Referencia</label>
                                    <input type="text" name="reference" x-model="form.reference" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Serie</label>
                                    <input type="text" name="serial" x-model="form.serial" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60">
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">DVR</label>
                                    <select name="dvr_id" x-model.number="form.dvr_id" required :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60">
                                        <option value="">Seleccionar…</option>
                                        <template x-for="dvr in dvrs" :key="dvr.id">
                                            <option :value="dvr.id" x-text="dvr.label + ' (' + dvr.ports + ' Ch)'"></option>
                                        </template>
                                    </select>
                                    <p x-show="dvrs.length === 0" class="mt-1 text-xs text-amber-600">Este proyecto no tiene DVRs. Agrégalos primero.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Canal</label>
                                    <select name="channel" x-model.number="form.channel" required :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none disabled:opacity-60">
                                        <option value="">Seleccionar…</option>
                                        <template x-for="ch in availableChannels" :key="ch">
                                            <option :value="ch" x-text="'Canal ' + ch"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Foto de la cámara</label>
                                <div class="mt-1.5 flex items-start gap-3">
                                    <template x-if="form.photo_url || form.photoPreview">
                                        <button type="button" @click="openPhotoLightbox(form.photoPreview || form.photo_url)" class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200">
                                            <img :src="form.photoPreview || form.photo_url" alt="Foto" class="h-full w-full object-cover">
                                        </button>
                                    </template>
                                    <input type="file" name="photo" accept=".png,.jpg,.jpeg" :disabled="saving" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white" @change="onPhotoChange($event)">
                                </div>
                                <p class="mt-1 text-xs text-slate-400">Clic en la miniatura para verla a pantalla completa.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
                            <div>
                                <template x-if="formMode === 'edit'">
                                    <button type="button" @click="confirmDelete()" :disabled="saving" class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50">Eliminar cámara</button>
                                </template>
                            </div>
                            <div class="flex gap-3">
                                <button type="button" @click="cancelForm()" :disabled="saving" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 disabled:opacity-50">Cancelar</button>
                                <button type="submit" :disabled="dvrs.length === 0 || saving" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                                    <svg x-show="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="saving ? 'Guardando…' : 'Guardar'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                    <form x-ref="deleteForm" method="POST" :action="formCamera?.delete_url" class="hidden" @submit="saving = true">
                        @csrf
                        @method('DELETE')
                    </form>

                    {{-- Overlay loader --}}
                    <div
                        x-show="saving"
                        x-cloak
                        x-transition.opacity.duration.150ms
                        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-2xl bg-white/80 backdrop-blur-[1px]"
                    >
                        <svg class="h-10 w-10 animate-spin text-slate-800" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-sm font-medium text-slate-700" x-text="formMode === 'edit' ? 'Actualizando cámara…' : 'Creando cámara…'"></p>
                    </div>
                </div>
            </div>

            {{-- Lightbox foto --}}
            <div
                x-show="photoLightbox"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/90 p-4"
                @click="photoLightbox = null"
                @keydown.escape.window="photoLightbox = null"
            >
                <button type="button" class="absolute right-5 top-5 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20" @click="photoLightbox = null">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <img
                    :src="photoLightbox"
                    alt="Foto cámara"
                    class="max-h-[90vh] max-w-[90vw] object-contain"
                    x-show="photoLightbox"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    @click.stop
                >
            </div>

            {{-- Toast --}}
            <div
                x-show="toast.visible"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="fixed bottom-6 right-6 z-[80] flex max-w-sm items-start gap-3 rounded-xl px-4 py-3 shadow-lg"
                :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
            >
                <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/20">
                    <template x-if="toast.type === 'success'">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </template>
                    <template x-if="toast.type !== 'success'">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" x-text="toast.type === 'success' ? 'Listo' : 'Error'"></p>
                    <p class="text-sm text-white/90" x-text="toast.message"></p>
                </div>
                <button type="button" class="shrink-0 text-white/80 hover:text-white" @click="toast.visible = false">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" x-data="dvrInventory()">
        @if ($errors->has('dvr'))
            <div class="border-b border-red-200 bg-red-50 px-5 py-3 text-sm text-red-700">{{ $errors->first('dvr') }}</div>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Inventario de Equipos DVR</h2>
            <button type="button" @click="openCreate()" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Agregar DVR
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Marca</th>
                        <th class="px-5 py-3">Serie / Modelo</th>
                        <th class="px-5 py-3">Puertos</th>
                        <th class="px-5 py-3">Cámaras</th>
                        <th class="px-5 py-3">Discos Duros</th>
                        <th class="px-5 py-3">Dirección IP</th>
                        <th class="px-5 py-3">Ubicación Física</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($project->dvrs as $dvr)
                        <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/80">
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-2 font-medium text-slate-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $dvr->brand ?: '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $dvr->serial_model ?: '—' }}</td>
                            <td class="px-5 py-4 font-mono text-slate-600">{{ $dvr->ports }} CH</td>
                            <td class="px-5 py-4 font-mono font-semibold text-slate-800">{{ $dvr->cameras_count }}/{{ $dvr->ports }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $dvr->disks }} disco(s)</td>
                            <td class="px-5 py-4">
                                <span class="rounded bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700">{{ $dvr->ip_address ?: 'N/A' }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $dvr->physical_location ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    ONLINE
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('projects.dvrs.show', [$project, $dvr]) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Consultar</a>
                                    @php
                                        $dvrEditPayload = [
                                            'id' => $dvr->id,
                                            'brand' => $dvr->brand,
                                            'serial_model' => $dvr->serial_model,
                                            'ports' => $dvr->ports,
                                            'disks' => $dvr->disks,
                                            'ip_address' => $dvr->ip_address,
                                            'physical_location' => $dvr->physical_location,
                                            'update_url' => route('projects.dvrs.update', [$project, $dvr]),
                                            'delete_url' => route('projects.dvrs.destroy', [$project, $dvr]),
                                        ];
                                    @endphp
                                    <button type="button" @click="openEdit({{ \Illuminate\Support\Js::from($dvrEditPayload) }})" class="rounded-lg px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50">Editar</button>
                                    <form method="POST" action="{{ route('projects.dvrs.destroy', [$project, $dvr]) }}" onsubmit="return confirm('¿Eliminar este DVR?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-400">Este proyecto no tiene DVRs registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-3">
            <p class="text-sm text-slate-400">Mostrando {{ $project->dvrs->count() }} de {{ $project->dvrs->count() }} DVRs registrados</p>
        </div>

        {{-- Modal crear/editar DVR --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.outside="open = false"
                 x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <form method="POST" :action="mode === 'edit' ? form.update_url : '{{ route('projects.dvrs.store', $project) }}'">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" x-bind:disabled="mode !== 'edit'">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-bold text-slate-900" x-text="mode === 'edit' ? 'Editar DVR' : 'Agregar DVR'"></h3>
                    </div>
                    <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Marca</label>
                            <input type="text" name="brand" x-model="form.brand" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Serie / Modelo</label>
                            <input type="text" name="serial_model" x-model="form.serial_model" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Puertos</label>
                            <select name="ports" x-model.number="form.ports" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                                <option :value="4">4 Ch</option>
                                <option :value="8">8 Ch</option>
                                <option :value="16">16 Ch</option>
                                <option :value="32">32 Ch</option>
                                <option :value="64">64 Ch</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Discos</label>
                            <input type="number" min="0" name="disks" x-model.number="form.disks" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Dirección IP</label>
                            <input type="text" name="ip_address" x-model="form.ip_address" placeholder="192.168.1.100" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Ubicación física</label>
                            <input type="text" name="physical_location" x-model="form.physical_location" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="open = false" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600">Cancelar</button>
                        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function dvrInventory() {
            return {
                open: false,
                mode: 'create',
                form: { brand: '', serial_model: '', ports: 16, disks: 1, ip_address: '', physical_location: '', update_url: '' },
                openCreate() {
                    this.mode = 'create';
                    this.form = { brand: '', serial_model: '', ports: 16, disks: 1, ip_address: '', physical_location: '', update_url: '' };
                    this.open = true;
                },
                openEdit(dvr) {
                    this.mode = 'edit';
                    this.form = { ...dvr };
                    this.open = true;
                },
            };
        }
    </script>

    <script>
        function planViewer(projectId, sheets, dvrs, openOnLoad, flash) {
            return {
                open: !!openOnLoad && sheets.length > 0,
                showAdd: false,
                sheets: sheets || [],
                dvrs: dvrs || [],
                activeIndex: 0,
                scale: 1,
                rotation: 0,
                tempPoint: null,
                formOpen: false,
                formMode: 'create',
                formCamera: null,
                formPos: { x: 0, y: 0 },
                form: { name: '', description: '', brand: '', reference: '', serial: '', dvr_id: '', channel: '', photo_url: null, photoPreview: null },
                hoverCam: null,
                hoverTimer: null,
                photoLightbox: null,
                saving: false,
                toast: { visible: false, type: 'success', message: '' },
                get activeSheet() {
                    return this.sheets[this.activeIndex] || null;
                },
                get activeCameras() {
                    return this.activeSheet?.cameras || [];
                },
                get availableChannels() {
                    const dvr = this.dvrs.find(d => d.id === Number(this.form.dvr_id));
                    if (!dvr) return [];
                    const used = new Set(dvr.used || []);
                    if (this.formMode === 'edit' && this.formCamera && this.formCamera.dvr_id === dvr.id) {
                        used.delete(Number(this.formCamera.channel));
                    }
                    const list = [];
                    for (let i = 1; i <= dvr.ports; i++) {
                        if (!used.has(i)) list.push(i);
                    }
                    return list;
                },
                storageKey() {
                    return 'planView:' + projectId + ':' + (this.activeSheet?.id ?? 'none');
                },
                init() {
                    this.loadView();
                    this.$watch('activeIndex', () => this.loadView());
                    this.$watch('scale', () => this.save());
                    this.$watch('rotation', () => this.save());
                    this.$watch('form.dvr_id', () => {
                        if (!this.availableChannels.includes(Number(this.form.channel))) {
                            this.form.channel = this.availableChannels[0] || '';
                        }
                    });
                    if (flash?.success) this.showToast('success', flash.success);
                    if (flash?.error) this.showToast('error', flash.error);
                },
                showToast(type, message) {
                    this.toast = { visible: true, type, message };
                    clearTimeout(this._toastTimer);
                    this._toastTimer = setTimeout(() => { this.toast.visible = false; }, 4200);
                },
                showHover(cam) {
                    clearTimeout(this.hoverTimer);
                    this.hoverCam = cam;
                },
                hideHover() {
                    clearTimeout(this.hoverTimer);
                    this.hoverTimer = setTimeout(() => { this.hoverCam = null; }, 180);
                },
                loadView() {
                    try {
                        const saved = JSON.parse(localStorage.getItem(this.storageKey()));
                        this.scale = saved?.scale ?? 1;
                        this.rotation = saved?.rotation ?? 0;
                    } catch (e) {
                        this.scale = 1;
                        this.rotation = 0;
                    }
                },
                save() {
                    if (!this.activeSheet) return;
                    localStorage.setItem(this.storageKey(), JSON.stringify({ scale: this.scale, rotation: this.rotation }));
                },
                selectSheet(i) { this.activeIndex = i; },
                openViewer() { if (this.activeSheet?.isImage) this.open = true; },
                closeViewer() {
                    if (this.saving) return;
                    this.open = false;
                    this.cancelForm();
                },
                prevSheet() {
                    if (this.sheets.length < 2) return;
                    this.activeIndex = (this.activeIndex - 1 + this.sheets.length) % this.sheets.length;
                },
                nextSheet() {
                    if (this.sheets.length < 2) return;
                    this.activeIndex = (this.activeIndex + 1) % this.sheets.length;
                },
                zoomIn() { this.scale = Math.min(6, +(this.scale + 0.25).toFixed(2)); },
                zoomOut() { this.scale = Math.max(0.25, +(this.scale - 0.25).toFixed(2)); },
                rotateCW() { this.rotation = (this.rotation + 90) % 360; },
                rotateCCW() { this.rotation = (this.rotation - 90 + 360) % 360; },
                reset() { this.scale = 1; this.rotation = 0; },
                onPlanClick(e) {
                    if (!this.open || this.formOpen || this.saving) return;
                    const img = e.currentTarget;
                    const rect = img.getBoundingClientRect();
                    if (rect.width <= 0 || rect.height <= 0) return;
                    const x = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                    const y = Math.min(100, Math.max(0, ((e.clientY - rect.top) / rect.height) * 100));
                    this.tempPoint = { x, y };
                    this.formMode = 'create';
                    this.formCamera = null;
                    this.formPos = { x: +x.toFixed(4), y: +y.toFixed(4) };
                    this.form = {
                        name: '', description: '', brand: '', reference: '', serial: '',
                        dvr_id: this.dvrs[0]?.id || '', channel: '', photo_url: null, photoPreview: null,
                    };
                    this.$nextTick(() => {
                        this.form.channel = this.availableChannels[0] || '';
                    });
                    this.formOpen = true;
                },
                openEdit(cam) {
                    if (this.saving) return;
                    this.tempPoint = null;
                    this.formMode = 'edit';
                    this.formCamera = cam;
                    this.formPos = { x: cam.pos_x, y: cam.pos_y };
                    this.form = {
                        name: cam.name || '',
                        description: cam.description || '',
                        brand: cam.brand || '',
                        reference: cam.reference || '',
                        serial: cam.serial || '',
                        dvr_id: cam.dvr_id,
                        channel: cam.channel,
                        photo_url: cam.photo_url,
                        photoPreview: null,
                    };
                    this.formOpen = true;
                },
                cancelForm() {
                    if (this.saving) return;
                    this.formOpen = false;
                    this.tempPoint = null;
                    this.formCamera = null;
                    this.form.photoPreview = null;
                },
                onFormSubmit(e) {
                    if (this.dvrs.length === 0) {
                        e.preventDefault();
                        this.showToast('error', 'Agrega un DVR antes de crear la cámara.');
                        return;
                    }
                    this.saving = true;
                },
                onPhotoChange(e) {
                    const file = e.target.files?.[0];
                    if (!file) {
                        this.form.photoPreview = null;
                        return;
                    }
                    this.form.photoPreview = URL.createObjectURL(file);
                },
                openPhotoLightbox(url) {
                    if (url) this.photoLightbox = url;
                },
                confirmDelete() {
                    if (!this.formCamera || this.saving) return;
                    if (!confirm('¿Eliminar la cámara «' + this.formCamera.name + '»?')) return;
                    this.saving = true;
                    this.$refs.deleteForm.submit();
                },
                onKey(e) {
                    if (this.photoLightbox) {
                        if (e.key === 'Escape') this.photoLightbox = null;
                        return;
                    }
                    if (this.formOpen) {
                        if (e.key === 'Escape' && !this.saving) this.cancelForm();
                        return;
                    }
                    if (!this.open) return;
                    if (e.key === 'Escape') this.closeViewer();
                    else if (e.key === '+' || e.key === '=') { e.preventDefault(); this.zoomIn(); }
                    else if (e.key === '-' || e.key === '_') { e.preventDefault(); this.zoomOut(); }
                    else if (e.key.toLowerCase() === 'r') this.rotateCW();
                    else if (e.key === '0') this.reset();
                    else if (e.key === 'ArrowLeft') this.prevSheet();
                    else if (e.key === 'ArrowRight') this.nextSheet();
                },
            };
        }
    </script>
</x-layout>
