@php
    $statusStyles = [
        'activo' => ['label' => 'Activo', 'variant' => 'success'],
        'instalacion' => ['label' => 'En Instalación', 'variant' => 'info'],
        'mantenimiento' => ['label' => 'Mantenimiento', 'variant' => 'warning'],
        'borrador' => ['label' => 'Borrador', 'variant' => 'muted'],
    ];
    $s = $statusStyles[$project->status] ?? $statusStyles['borrador'];
    $quoteVariants = [
        'borrador' => 'muted',
        'emitida' => 'info',
        'aprobada' => 'success',
        'rechazada' => 'error',
        'convertida' => 'accent',
        'cancelada' => 'muted',
    ];
    $orderVariants = [
        'pendiente' => 'warning',
        'en_progreso' => 'info',
        'completada' => 'success',
        'cancelada' => 'muted',
    ];
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
    $previewSheet = $project->floorPlans->first(fn ($fp) => $fp->isImage()) ?? $project->floorPlans->first();
    $activeTab = $activeTab ?? 'resumen';
@endphp

<x-layout :title="$project->name.' · CCTV Manager'" active="proyectos">
    @php
        $flashToast = [
            'success' => session('status'),
            'error' => $errors->any() ? $errors->first() : null,
        ];
    @endphp

    <x-ui.page-header
        :title="$project->name"
        :description="'ID Proyecto: '.($project->code ?? 'N/A')"
    >
        <x-slot:breadcrumbs>
            <x-ui.breadcrumbs :items="[
                ['label' => 'Proyectos', 'href' => route('projects')],
                ['label' => $project->name],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('projects.quotations.create', $project)">
                Nueva cotización
            </x-ui.button>
            <x-ui.button variant="outline" type="button">
                Generar Reporte PDF
            </x-ui.button>
            <x-ui.button type="button">
                Editar Proyecto
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <x-ui.badge :variant="$s['variant']" dot>{{ $s['label'] }}</x-ui.badge>
        <x-ui.badge variant="muted">{{ $project->type }}</x-ui.badge>
    </div>

    <x-ui.tabs
        :tabs="[
            'resumen' => 'Resumen',
            'plano' => 'Plano de la Unidad',
            'info' => 'Información',
            'cotizaciones' => 'Cotizaciones',
            'ordenes' => 'Órdenes',
            'dvr' => 'DVR',
            'camaras' => 'Cámaras',
            'inventario' => 'Inventario',
            'trazabilidad' => 'Trazabilidad',
        ]"
        :active="$activeTab"
    >
        {{-- Resumen --}}
        <div x-show="activeTab === 'resumen'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="Total DVRs" :value="str_pad((string) $project->dvrs->count(), 2, '0', STR_PAD_LEFT)" />
                <x-ui.stat-card label="Total Cámaras" :value="(string) $totalCameras" />
                <x-ui.stat-card label="Uptime" value="99.8%" />
                <x-ui.stat-card label="Último Mantenimiento" :value="optional($project->updated_at)->format('d/m/y') ?? 'N/A'" />
            </div>

            <x-ui.card class="mt-6" title="Cotizaciones recientes">
                <x-slot:header>
                    <x-ui.button variant="ghost" size="sm" :href="route('cotizaciones')">Ver todas</x-ui.button>
                </x-slot:header>
                <ul class="divide-y divide-border-subtle text-sm">
                    @forelse ($project->quotations->take(5) as $quote)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <a class="font-medium text-foreground hover:text-accent hover:underline" href="{{ route('projects.quotations.show', [$project, $quote->id]) }}">{{ $quote->code }}</a>
                                <x-ui.badge :variant="$quoteVariants[$quote->status] ?? 'muted'" dot>{{ ucfirst($quote->status) }}</x-ui.badge>
                            </div>
                            <span class="font-mono text-foreground-muted">{{ $quote->total }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-foreground-muted">Sin cotizaciones. Crea la primera desde “Nueva cotización”.</li>
                    @endforelse
                </ul>
            </x-ui.card>

            <x-ui.card class="mt-6" :padding="false">
                <x-slot:header>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Plano de la Unidad</h2>
                        <p class="mt-0.5 text-sm text-foreground-muted">Topología y distribución de cámaras en el proyecto.</p>
                    </div>
                    <x-ui.button size="sm" type="button" @click="setTab('plano')">
                        Abrir plano
                    </x-ui.button>
                </x-slot:header>
                <div class="p-5">
                    @if ($previewSheet && $previewSheet->isImage())
                        <button type="button" @click="setTab('plano')" class="group relative block h-56 w-full overflow-hidden rounded-lg border border-border bg-muted motion-reduce:transition-none" aria-label="Abrir Plano de la Unidad">
                            <img src="{{ $previewSheet->url() }}" alt="{{ $previewSheet->name ?: 'Plano de la Unidad' }}" class="h-full w-full object-cover transition duration-medium ease-standard group-hover:scale-[1.03] motion-reduce:transform-none">
                            <span class="pointer-events-none absolute inset-0 bg-foreground/0 transition duration-fast group-hover:bg-foreground/20"></span>
                            <span class="pointer-events-none absolute bottom-3 left-3 rounded-md bg-foreground/80 px-2.5 py-1.5 text-xs font-medium text-background">{{ $previewSheet->name ?: 'Hoja 1' }} · {{ $project->floorPlans->count() }} hoja(s)</span>
                        </button>
                    @elseif ($previewSheet)
                        <button type="button" @click="setTab('plano')" class="flex h-40 w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border text-sm text-foreground-muted hover:border-accent hover:text-accent">
                            Abrir PDF · {{ $previewSheet->name }}
                        </button>
                    @else
                        <x-ui.empty-state
                            title="Sin plano"
                            description="Agrega la primera hoja para colocar cámaras sobre el Plano de la Unidad."
                        >
                            <x-ui.button type="button" class="mt-6" @click="setTab('plano')">Agregar hoja</x-ui.button>
                        </x-ui.empty-state>
                    @endif
                </div>
            </x-ui.card>
        </div>

        {{-- Información --}}
        <div x-show="activeTab === 'info'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui.card title="Información General">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Dirección</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ $project->address ?: 'Sin dirección' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Barrio</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ $project->neighborhood ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Ciudad</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ $project->city ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Fecha de Instalación</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ optional($project->created_at)->translatedFormat('d \d\e F, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        {{-- Cotizaciones --}}
        <div x-show="activeTab === 'cotizaciones'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-foreground">Cotizaciones</h2>
                    <x-ui.button size="sm" :href="route('projects.quotations.create', $project)">Nueva cotización</x-ui.button>
                </x-slot:header>
                <x-ui.data-table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Estado</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->quotations as $quote)
                            <tr>
                                <td class="font-medium">{{ $quote->code }}</td>
                                <td>
                                    <x-ui.badge :variant="$quoteVariants[$quote->status] ?? 'muted'" dot>{{ ucfirst($quote->status) }}</x-ui.badge>
                                </td>
                                <td class="text-right font-mono">{{ $quote->total }}</td>
                                <td class="text-right">
                                    <x-ui.button variant="ghost" size="sm" :href="route('projects.quotations.show', [$project, $quote->id])">Ver</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-ui.empty-state
                                        title="Sin cotizaciones"
                                        description="Crea la primera cotización desde el botón Nueva cotización."
                                        action-label="Nueva cotización"
                                        :action-href="route('projects.quotations.create', $project)"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.data-table>
            </x-ui.card>
        </div>

        {{-- Órdenes --}}
        <div x-show="activeTab === 'ordenes'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui.card title="Órdenes de instalación">
                <x-ui.data-table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cotización</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->installationOrders as $order)
                            <tr>
                                <td class="font-medium">{{ $order->code }}</td>
                                <td>{{ $order->quotation?->code ?? '—' }}</td>
                                <td>
                                    <x-ui.badge :variant="$orderVariants[$order->status] ?? 'muted'" dot>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</x-ui.badge>
                                </td>
                                <td class="text-right">
                                    <x-ui.button variant="ghost" size="sm" :href="route('projects.orders.show', [$project, $order->id])">Ver</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-ui.empty-state
                                        title="Sin órdenes"
                                        description="Las órdenes se generan al convertir una cotización aprobada."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.data-table>
            </x-ui.card>
        </div>

        {{-- Plano de la Unidad --}}
        <div
            x-show="activeTab === 'plano'"
            x-cloak
            x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <div
                x-data="planViewer({{ $project->id }}, {{ \Illuminate\Support\Js::from($sheets) }}, {{ \Illuminate\Support\Js::from($dvrsPayload) }}, {{ session('open_plan_viewer') || $errors->any() ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($flashToast) }})"
            >
            <x-ui.card :padding="false" class="motion-fade-in">
                <x-slot:header>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Plano de la Unidad</h2>
                        <p class="mt-0.5 text-sm text-foreground-muted">Carga hojas, amplía el plano y coloca cámaras.</p>
                    </div>
                    <x-ui.button size="sm" type="button" @click="showAdd = true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Agregar hoja
                    </x-ui.button>
                </x-slot:header>

                <div class="p-5">
                    <div class="mb-3 flex flex-wrap items-center gap-2" x-show="sheets.length > 0">
                        <template x-for="(sheet, i) in sheets" :key="sheet.id">
                            <div class="relative pr-2 pt-2">
                                <button type="button" @click="selectSheet(i)" class="min-h-11 rounded-lg border px-3 py-2 text-xs font-semibold transition duration-fast ease-standard" :class="activeIndex === i ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-surface text-foreground-muted hover:bg-muted'" x-text="sheet.name"></button>
                                <form :action="sheet.deleteUrl" method="POST" class="absolute right-0 top-0" @submit="if (!confirm('¿Eliminar la hoja «' + sheet.name + '»?')) $event.preventDefault()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex h-11 w-11 items-center justify-center text-on-accent" title="Eliminar hoja" aria-label="Eliminar hoja">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-destructive shadow">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </template>
                    </div>

                    <div class="relative h-80 overflow-hidden rounded-lg border border-border bg-muted">
                        <template x-if="activeSheet && activeSheet.isImage">
                            <button type="button" @click="openViewer()" class="group block h-full w-full" aria-label="Ampliar Plano de la Unidad">
                                <img :src="activeSheet.url" :alt="activeSheet.name" class="h-full w-full cursor-zoom-in object-cover transition duration-medium ease-standard group-hover:scale-[1.03] motion-reduce:transform-none">
                                <span class="pointer-events-none absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-md bg-foreground/80 px-2.5 py-1.5 text-xs font-medium text-background opacity-100 sm:opacity-0 sm:transition sm:group-hover:opacity-100">Pantalla completa · clic para agregar cámaras</span>
                            </button>
                        </template>
                        <template x-if="activeSheet && !activeSheet.isImage">
                            <a :href="activeSheet.url" target="_blank" class="flex h-full w-full flex-col items-center justify-center gap-2 text-foreground-muted hover:text-accent">
                                <span class="text-sm font-medium" x-text="'Abrir PDF · ' + activeSheet.name"></span>
                            </a>
                        </template>
                        <template x-if="!activeSheet">
                            <div class="flex h-full w-full flex-col items-center justify-center gap-3 text-sm text-foreground-muted">
                                <span>Sin hojas de plano. Agrega la primera.</span>
                                <x-ui.button size="sm" type="button" @click="showAdd = true">Agregar hoja</x-ui.button>
                            </div>
                        </template>
                    </div>
                </div>
            </x-ui.card>

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
                class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/50 p-4"
                @keydown.escape.window="if (showAdd && !saving) showAdd = false"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-surface shadow-xl"
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
                        <div class="border-b border-border px-6 py-4">
                            <h3 class="text-lg font-bold text-foreground">Agregar hoja de plano</h3>
                        </div>
                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="block text-sm font-medium text-foreground">Nombre de la hoja</label>
                                <input type="text" name="floor_plan_names[]" placeholder="Ej: Piso 2" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Archivo</label>
                                <input type="file" name="floor_plans[]" accept=".png,.jpg,.jpeg,.pdf" required multiple class="mt-1.5 block w-full text-sm text-foreground-muted file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-semibold file:text-on-accent">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-border px-6 py-4">
                            <x-ui.button variant="outline" type="button" @click="showAdd = false">Cancelar</x-ui.button>
                            <x-ui.button type="submit">Guardar hoja(s)</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Visor pantalla completa --}}
            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-enter duration-medium motion-reduce:transition-none"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-exit duration-fast motion-reduce:transition-none"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[70] flex flex-col bg-foreground/90 backdrop-blur-sm"
                @keydown.window="onKey($event)"
                role="dialog"
                aria-modal="true"
                aria-label="Plano de la Unidad"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-background/15 px-4 py-3 text-background sm:px-5">
                    <span class="truncate text-sm font-medium" x-text="'Plano de la Unidad · ' + (activeSheet?.name || '') + ' · {{ $project->name }}'"></span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" @click="prevSheet()" :disabled="sheets.length < 2" class="flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20 disabled:opacity-30" aria-label="Hoja anterior">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        </button>
                        <span class="min-w-16 text-center text-xs tabular-nums" x-text="(activeIndex + 1) + ' / ' + sheets.length"></span>
                        <button type="button" @click="nextSheet()" :disabled="sheets.length < 2" class="flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20 disabled:opacity-30" aria-label="Hoja siguiente">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                        </button>
                        <span class="mx-1 hidden h-6 w-px bg-background/15 sm:block"></span>
                        <button type="button" @click="zoomOut()" class="flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20" title="Alejar" aria-label="Alejar">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                        <span class="w-14 text-center text-sm font-semibold tabular-nums" x-text="Math.round(scale * 100) + '%'"></span>
                        <button type="button" @click="zoomIn()" class="flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20" title="Acercar" aria-label="Acercar">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                        <span class="mx-1 hidden h-6 w-px bg-background/15 sm:block"></span>
                        <button type="button" @click="rotateCCW()" class="flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20" aria-label="Rotar a la izquierda"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg></button>
                        <button type="button" @click="rotateCW()" class="flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20" aria-label="Rotar a la derecha"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 100 12h3" /></svg></button>
                        <span class="mx-1 hidden h-6 w-px bg-background/15 sm:block"></span>
                        <button type="button" @click="reset()" class="flex h-11 items-center rounded-lg bg-background/10 px-3 text-sm font-medium hover:bg-background/20">Reset</button>
                        <button type="button" @click="closeViewer()" class="ml-1 flex h-11 w-11 items-center justify-center rounded-lg bg-background/10 hover:bg-background/20" aria-label="Cerrar visor"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
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
                                        class="plan-marker flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-destructive shadow ring-2 ring-destructive/40"
                                        :title="cam.name"
                                        :aria-label="'Cámara ' + cam.name"
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
                                        class="absolute left-5 top-1/2 z-20 flex min-w-48 -translate-y-1/2 items-center gap-2.5 rounded-lg bg-foreground px-2.5 py-2 shadow-xl"
                                        @mouseenter="showHover(cam)"
                                        @mouseleave="hideHover()"
                                    >
                                        <button
                                            type="button"
                                            class="h-10 w-10 shrink-0 overflow-hidden rounded bg-muted ring-0 transition hover:ring-2 hover:ring-accent"
                                            @click.stop="if (cam.photo_url) openPhotoLightbox(cam.photo_url)"
                                            :title="cam.photo_url ? 'Ampliar foto' : 'Sin foto'"
                                        >
                                            <template x-if="cam.photo_url">
                                                <img :src="cam.photo_url" alt="" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!cam.photo_url">
                                                <div class="flex h-full w-full items-center justify-center text-[10px] text-foreground-muted">N/A</div>
                                            </template>
                                        </button>
                                        <div class="min-w-0 text-left">
                                            <p class="truncate text-sm font-semibold text-background" x-text="cam.name"></p>
                                            <p class="truncate text-xs text-accent" x-text="[cam.brand, cam.reference, cam.serial].filter(Boolean).join(' · ') || 'Sin marca'"></p>
                                            <p class="text-[10px] text-foreground-muted" x-text="cam.dvr_label + ' · Ch ' + cam.channel"></p>
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
                                <span class="block h-4 w-4 animate-pulse rounded-full border-2 border-white bg-accent shadow"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="pb-3 text-center text-xs text-background/50">Clic en el plano para agregar una cámara · Clic en un punto para editarla</p>
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
                class="fixed inset-0 z-[80] flex items-center justify-center bg-foreground/60 p-4"
                @keydown.escape.window="if (formOpen && !photoLightbox && !saving) cancelForm()"
            >
                <div
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-surface shadow-xl"
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

                        <div class="border-b border-border px-6 py-4">
                            <h3 class="text-lg font-bold text-foreground" x-text="formMode === 'edit' ? 'Vista previa / Editar cámara' : 'Nueva cámara'"></h3>
                            <p class="mt-0.5 text-sm text-foreground-muted" x-text="formMode === 'edit' ? 'Actualiza los datos de la cámara en el plano.' : 'Completa los datos para fijar el punto en el plano.'"></p>
                        </div>

                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="block text-sm font-medium text-foreground">Nombre</label>
                                <input type="text" name="name" x-model="form.name" required :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Descripción</label>
                                <textarea name="description" x-model="form.description" rows="2" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60"></textarea>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-foreground">Marca</label>
                                    <input type="text" name="brand" x-model="form.brand" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-foreground">Referencia</label>
                                    <input type="text" name="reference" x-model="form.reference" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-foreground">Serie</label>
                                    <input type="text" name="serial" x-model="form.serial" :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-foreground">DVR</label>
                                    <select name="dvr_id" x-model.number="form.dvr_id" required :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                        <option value="">Seleccionar…</option>
                                        <template x-for="dvr in dvrs" :key="dvr.id">
                                            <option :value="dvr.id" x-text="dvr.label + ' (' + dvr.ports + ' Ch)'"></option>
                                        </template>
                                    </select>
                                    <p x-show="dvrs.length === 0" class="mt-1 text-xs text-warning">Este proyecto no tiene DVRs. Agrégalos primero.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-foreground">Canal</label>
                                    <select name="channel" x-model.number="form.channel" required :disabled="saving" class="mt-1.5 block w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm shadow-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-60">
                                        <option value="">Seleccionar…</option>
                                        <template x-for="ch in availableChannels" :key="ch">
                                            <option :value="ch" x-text="'Canal ' + ch"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Foto de la cámara</label>
                                <div class="mt-1.5 flex items-start gap-3">
                                    <template x-if="form.photo_url || form.photoPreview">
                                        <button type="button" @click="openPhotoLightbox(form.photoPreview || form.photo_url)" class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-border">
                                            <img :src="form.photoPreview || form.photo_url" alt="Foto" class="h-full w-full object-cover">
                                        </button>
                                    </template>
                                    <input type="file" name="photo" accept=".png,.jpg,.jpeg" :disabled="saving" class="block w-full text-sm text-foreground-muted file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-semibold file:text-on-accent" @change="onPhotoChange($event)">
                                </div>
                                <p class="mt-1 text-xs text-foreground-muted">Clic en la miniatura para verla a pantalla completa.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-6 py-4">
                            <div>
                                <template x-if="formMode === 'edit'">
                                    <x-ui.button variant="destructive" size="sm" type="button" @click="confirmDelete()" x-bind:disabled="saving">Eliminar cámara</x-ui.button>
                                </template>
                            </div>
                            <div class="flex gap-3">
                                <x-ui.button variant="outline" type="button" @click="cancelForm()" x-bind:disabled="saving">Cancelar</x-ui.button>
                                <x-ui.button type="submit" x-bind:disabled="dvrs.length === 0 || saving" x-bind:loading="saving">
                                    <span x-text="saving ? 'Guardando…' : 'Guardar'"></span>
                                </x-ui.button>
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
                        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-2xl bg-surface/80 backdrop-blur-[1px]"
                    >
                        <svg class="h-10 w-10 animate-spin text-foreground" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-sm font-medium text-foreground-muted" x-text="formMode === 'edit' ? 'Actualizando cámara…' : 'Creando cámara…'"></p>
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
                class="fixed inset-0 z-[90] flex items-center justify-center bg-black/90 p-4"
                @click="photoLightbox = null"
                @keydown.escape.window="photoLightbox = null"
            >
                <button type="button" class="absolute right-5 top-5 rounded-full bg-background/10 p-2 text-background transition hover:bg-background/20" @click="photoLightbox = null">
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
                class="fixed bottom-6 right-6 z-[95] flex max-w-sm items-start gap-3 rounded-xl px-4 py-3 shadow-lg"
                :class="toast.type === 'success' ? 'bg-success text-background' : 'bg-destructive text-background'"
            >
                <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-background/20">
                    <template x-if="toast.type === 'success'">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </template>
                    <template x-if="toast.type !== 'success'">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" x-text="toast.type === 'success' ? 'Listo' : 'Error'"></p>
                    <p class="text-sm text-background/90" x-text="toast.message"></p>
                </div>
                <button type="button" class="shrink-0 text-background/80 hover:text-background" @click="toast.visible = false">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            </div>
        </div>

        {{-- DVR --}}
        <div x-show="activeTab === 'dvr'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-data="dvrInventory()">
            @if ($errors->has('dvr'))
                <x-ui.alert variant="error" class="mb-4">{{ $errors->first('dvr') }}</x-ui.alert>
            @endif
            <x-ui.card :padding="false">
                <x-slot:header>
                    <h2 class="text-base font-semibold text-foreground">Equipos DVR</h2>
                    <x-ui.button size="sm" type="button" @click="openCreate()">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Agregar DVR
                    </x-ui.button>
                </x-slot:header>
                <x-ui.data-table>
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Serie / Modelo</th>
                            <th>Puertos</th>
                            <th>Cámaras</th>
                            <th>Discos Duros</th>
                            <th>Dirección IP</th>
                            <th>Ubicación Física</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->dvrs as $dvr)
                            <tr>
                                <td>
                                    <span class="inline-flex items-center gap-2 font-medium text-foreground">
                                        <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                                        {{ $dvr->brand ?: '—' }}
                                    </span>
                                </td>
                                <td class="text-foreground-muted">{{ $dvr->serial_model ?: '—' }}</td>
                                <td class="font-mono text-foreground-muted">{{ $dvr->ports }} CH</td>
                                <td class="font-mono font-semibold text-foreground">{{ $dvr->cameras_count }}/{{ $dvr->ports }}</td>
                                <td class="text-foreground-muted">{{ $dvr->disks }} disco(s)</td>
                                <td>
                                    <span class="rounded bg-muted px-2 py-1 font-mono text-xs text-foreground">{{ $dvr->ip_address ?: 'N/A' }}</span>
                                </td>
                                <td class="text-foreground-muted">{{ $dvr->physical_location ?: '—' }}</td>
                                <td>
                                    <x-ui.badge variant="success" dot>ONLINE</x-ui.badge>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <x-ui.button variant="ghost" size="sm" :href="route('projects.dvrs.show', [$project, $dvr])">Consultar</x-ui.button>
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
                                        <x-ui.button variant="ghost" size="sm" type="button" @click="openEdit({{ \Illuminate\Support\Js::from($dvrEditPayload) }})">Editar</x-ui.button>
                                        <form method="POST" action="{{ route('projects.dvrs.destroy', [$project, $dvr]) }}" onsubmit="return confirm('¿Eliminar este DVR?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button variant="destructive" size="sm" type="submit">Eliminar</x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <x-ui.empty-state title="Sin DVRs" description="Este proyecto no tiene DVRs registrados." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.data-table>
                <x-slot:footer>
                    <p class="text-sm text-foreground-muted">Mostrando {{ $project->dvrs->count() }} de {{ $project->dvrs->count() }} DVRs registrados</p>
                </x-slot:footer>
            </x-ui.card>

            {{-- Modal crear/editar DVR --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/50 p-4"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="w-full max-w-lg rounded-2xl bg-surface shadow-xl" @click.outside="open = false"
                     x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <form method="POST" :action="mode === 'edit' ? form.update_url : '{{ route('projects.dvrs.store', $project) }}'">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" x-bind:disabled="mode !== 'edit'">
                        <div class="border-b border-border px-6 py-4">
                            <h3 class="text-lg font-bold text-foreground" x-text="mode === 'edit' ? 'Editar DVR' : 'Agregar DVR'"></h3>
                        </div>
                        <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-foreground">Marca</label>
                                <input type="text" name="brand" x-model="form.brand" class="mt-1.5 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Serie / Modelo</label>
                                <input type="text" name="serial_model" x-model="form.serial_model" class="mt-1.5 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Puertos</label>
                                <select name="ports" x-model.number="form.ports" class="mt-1.5 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option :value="4">4 Ch</option>
                                    <option :value="8">8 Ch</option>
                                    <option :value="16">16 Ch</option>
                                    <option :value="32">32 Ch</option>
                                    <option :value="64">64 Ch</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Discos</label>
                                <input type="number" min="0" name="disks" x-model.number="form.disks" class="mt-1.5 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Dirección IP</label>
                                <input type="text" name="ip_address" x-model="form.ip_address" placeholder="192.168.1.100" class="mt-1.5 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground">Ubicación física</label>
                                <input type="text" name="physical_location" x-model="form.physical_location" class="mt-1.5 w-full rounded-lg border border-border bg-surface px-3 py-2.5 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-border px-6 py-4">
                            <x-ui.button variant="outline" type="button" @click="open = false">Cancelar</x-ui.button>
                            <x-ui.button type="submit">Guardar</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Cámaras --}}
        <div x-show="activeTab === 'camaras'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui.card title="Cámaras del proyecto">
                <x-ui.data-table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Marca / Referencia</th>
                            <th>DVR</th>
                            <th>Canal</th>
                            <th>Plano</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->projectCameras as $cam)
                            <tr>
                                <td class="font-medium text-foreground">{{ $cam->name }}</td>
                                <td class="text-foreground-muted">{{ trim(($cam->brand ?? '').' '.($cam->reference ?? '')) ?: '—' }}</td>
                                <td class="text-foreground-muted">{{ trim(($cam->dvr?->brand ?? '').' '.($cam->dvr?->serial_model ?? '')) ?: 'DVR #'.$cam->dvr_id }}</td>
                                <td class="font-mono">{{ $cam->channel }}</td>
                                <td class="text-foreground-muted">{{ $cam->floorPlan?->name ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-ui.empty-state
                                        title="Sin cámaras"
                                        description="Agrega cámaras desde Plano de la Unidad colocándolas sobre el plano."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.data-table>
            </x-ui.card>
        </div>

        {{-- Inventario --}}
        <div x-show="activeTab === 'inventario'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.stat-card label="DVRs registrados" :value="(string) $project->dvrs->count()" />
                <x-ui.stat-card label="Cámaras instaladas" :value="(string) $totalCameras" />
                <x-ui.stat-card label="Capacidad total" :value="$totalPorts.' CH'" />
            </div>
            <x-ui.card class="mt-6" title="Inventario de equipos">
                <x-ui.data-table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Identificador</th>
                            <th>Detalle</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($project->dvrs as $dvr)
                            <tr>
                                <td><x-ui.badge variant="accent">DVR</x-ui.badge></td>
                                <td class="font-medium">{{ trim(($dvr->brand ?? '').' '.($dvr->serial_model ?? '')) ?: 'DVR #'.$dvr->id }}</td>
                                <td class="text-foreground-muted">{{ $dvr->ports }} CH · {{ $dvr->cameras_count }}/{{ $dvr->ports }} cámaras · {{ $dvr->disks }} disco(s)</td>
                                <td><x-ui.badge variant="success" dot>ONLINE</x-ui.badge></td>
                            </tr>
                        @endforeach
                        @foreach ($project->projectCameras as $cam)
                            <tr>
                                <td><x-ui.badge variant="info">Cámara</x-ui.badge></td>
                                <td class="font-medium">{{ $cam->name }}</td>
                                <td class="text-foreground-muted">{{ trim(($cam->brand ?? '').' '.($cam->reference ?? '')) ?: '—' }} · Ch {{ $cam->channel }}</td>
                                <td><x-ui.badge variant="success" dot>Activa</x-ui.badge></td>
                            </tr>
                        @endforeach
                        @if ($project->dvrs->isEmpty() && $project->projectCameras->isEmpty())
                            <tr>
                                <td colspan="4">
                                    <x-ui.empty-state title="Inventario vacío" description="Registra DVRs y cámaras para ver el inventario consolidado." />
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </x-ui.data-table>
            </x-ui.card>
        </div>

        {{-- Trazabilidad --}}
        <div x-show="activeTab === 'trazabilidad'" x-cloak x-transition:enter="transition ease-enter duration-fast motion-reduce:transition-none" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui.card title="Trazabilidad del proyecto">
                <p class="text-sm text-foreground-muted">
                    Consulta el historial de eventos de cotizaciones y órdenes vinculados a este proyecto.
                </p>
                <div class="mt-4">
                    <x-ui.button :href="route('trazabilidad', ['project_id' => $project->id])">
                        Ver trazabilidad completa
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </x-ui.tabs>

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
