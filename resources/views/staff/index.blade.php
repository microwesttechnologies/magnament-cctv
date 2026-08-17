@php
    $roleLabels = ['supervisor' => 'Supervisor', 'tecnico' => 'Técnico'];
    $statusStyles = [
        'activo' => 'bg-emerald-50 text-emerald-700',
        'inactivo' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-layout title="Personal · CCTV Manager" active="personal">
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Personal</h1>
            <p class="mt-1 text-slate-500">Supervisores y técnicos de la empresa</p>
        </div>
        <a href="{{ route('staff.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Agregar personal
        </a>
    </div>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-48 flex-1">
            <label class="block text-xs font-medium text-slate-500">Buscar</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre, cédula o correo" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Rol</label>
            <select name="role" class="mt-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="supervisor" @selected(($filters['role'] ?? '') === 'supervisor')>Supervisor</option>
                <option value="tecnico" @selected(($filters['role'] ?? '') === 'tecnico')>Técnico</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Estado</label>
            <select name="status" class="mt-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                <option value="">Todos</option>
                <option value="activo" @selected(($filters['status'] ?? '') === 'activo')>Activo</option>
                <option value="inactivo" @selected(($filters['status'] ?? '') === 'inactivo')>Inactivo</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Filtrar</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Nombre</th>
                        <th class="px-5 py-3">Documento</th>
                        <th class="px-5 py-3">Rol</th>
                        <th class="px-5 py-3">Contacto</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3">Herramientas</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($staff as $person)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/80">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 overflow-hidden rounded-full bg-slate-200">
                                        @if ($person->photoUrl())
                                            <img src="{{ $person->photoUrl() }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-slate-500">{{ strtoupper(substr($person->name, 0, 1)) }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $person->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $person->city ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $person->document_type }} {{ $person->document_number }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $roleLabels[$person->role] ?? $person->role }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                <p>{{ $person->phone ?: '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $person->email ?: '' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles[$person->status] ?? $statusStyles['inactivo'] }}">{{ ucfirst($person->status) }}</span>
                            </td>
                            <td class="px-5 py-4 font-mono text-slate-600">{{ $person->tools_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('staff.edit', $person) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">Editar</a>
                                    <form method="POST" action="{{ route('staff.destroy', $person) }}" onsubmit="return confirm('¿Eliminar a {{ $person->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-400">No hay personal registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
