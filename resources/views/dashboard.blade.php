@php
    $cards = [
        [
            'title' => 'Proyectos',
            'description' => 'Gestión de despliegues y topología de red de cámaras.',
            'href' => route('projects'),
            'accent' => 'bg-slate-900',
            'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        ],
        [
            'title' => 'Cotizaciones',
            'description' => 'Presupuestos de hardware NVR, discos y licencias VMS.',
            'accent' => 'bg-blue-600',
            'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        ],
        [
            'title' => 'Trazabilidad',
            'description' => 'Seguimiento de cambios de firmware y logs de servidor.',
            'accent' => 'bg-slate-900',
            'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'title' => 'Cuenta Cobro',
            'description' => 'Facturación por servicios de mantenimiento y soporte.',
            'accent' => 'bg-slate-900',
            'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
        ],
    ];
@endphp

<x-layout title="Panel · CCTV Manager" active="home">
    {{-- Encabezado + resumen --}}
    <div class="flex flex-wrap items-start justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Bienvenido, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-slate-500">Resumen de operaciones y estado de infraestructura IP.</p>
        </div>

        <div class="flex items-stretch divide-x divide-slate-200 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-6 py-3 text-center">
                <p class="text-2xl font-bold text-slate-900">12</p>
                <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">Instalaciones</p>
            </div>
            <div class="px-6 py-3 text-center">
                <p class="text-2xl font-bold text-amber-500">03</p>
                <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">Configurando</p>
            </div>
            <div class="px-6 py-3 text-center">
                <p class="text-2xl font-bold text-slate-900">98%</p>
                <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">Efectividad</p>
            </div>
        </div>
    </div>

    {{-- Tarjetas de módulos --}}
    <div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a href="{{ $card['href'] ?? '#' }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg {{ $card['accent'] }} text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $card['title'] }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $card['description'] }}</p>
            </a>
        @endforeach
    </div>
</x-layout>
