@php
    $label = trim(($dvr->brand ?? '').' '.($dvr->serial_model ?? '')) ?: 'DVR #'.$dvr->id;
@endphp

<x-layout :title="'DVR · '.$label.' · CCTV Manager'" active="proyectos">
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        Volver al proyecto
    </a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4" x-data="{ showSupport: {{ $errors->any() ? 'true' : 'false' }} }">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ $label }}</h1>
            <p class="mt-1 text-slate-500">Hoja de vida · Proyecto {{ $project->name }} ({{ $project->code }})</p>
        </div>
        <button type="button" @click="showSupport = true" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Agregar soporte
        </button>

        <div class="mt-6 grid w-full gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Marca</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $dvr->brand ?: '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Serie / Modelo</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $dvr->serial_model ?: '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Cámaras</p>
                <p class="mt-1 font-mono text-xl font-bold text-slate-900">{{ $dvr->cameras_count }}/{{ $dvr->ports }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">IP / Ubicación</p>
                <p class="mt-1 font-mono text-sm text-slate-900">{{ $dvr->ip_address ?: 'N/A' }}</p>
                <p class="text-xs text-slate-500">{{ $dvr->physical_location ?: '—' }}</p>
            </div>
        </div>

        <div class="mt-6 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Historial de soportes</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($dvr->supports as $support)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $support->title }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    Responsable: {{ $support->staff?->name ?? '—' }}
                                    · {{ optional($support->created_at)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $support->evidences->count() }} evidencia(s)</span>
                        </div>
                        @if ($support->description)
                            <p class="mt-2 text-sm text-slate-700">{{ $support->description }}</p>
                        @endif
                        @if ($support->evidences->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($support->evidences as $ev)
                                    <a href="{{ $ev->url() }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-blue-50 hover:text-blue-700">
                                        {{ $ev->original_name ?: 'Evidencia' }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-12 text-center text-sm text-slate-400">Aún no hay soportes registrados para este DVR.</div>
                @endforelse
            </div>
        </div>

        {{-- Modal agregar soporte --}}
        <div x-show="showSupport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.outside="showSupport = false"
                 x-show="showSupport" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <form method="POST" action="{{ route('projects.dvrs.supports.store', [$project, $dvr]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-bold text-slate-900">Agregar soporte</h3>
                        <p class="mt-0.5 text-sm text-slate-500">La fecha de creación se registra automáticamente.</p>
                    </div>
                    <div class="space-y-4 px-6 py-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Responsable (técnico activo)</label>
                            <select name="staff_id" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                                <option value="">Seleccionar…</option>
                                @forelse ($technicians as $tech)
                                    <option value="{{ $tech->id }}" @selected(old('staff_id') == $tech->id)>{{ $tech->name }} ({{ $tech->document_number }})</option>
                                @empty
                                    <option value="" disabled>No hay técnicos activos. Créalos en Personal.</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Título</label>
                            <input type="text" name="title" value="{{ old('title') }}" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Descripción</label>
                            <textarea name="description" rows="3" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">{{ old('description') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Evidencias</label>
                            <input type="file" name="evidences[]" accept=".png,.jpg,.jpeg,.pdf" multiple class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                            <p class="mt-1 text-xs text-slate-400">Puedes seleccionar varios archivos (PNG, JPG, PDF).</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button type="button" @click="showSupport = false" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600">Cancelar</button>
                        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white" @disabled($technicians->isEmpty())>Guardar soporte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layout>
