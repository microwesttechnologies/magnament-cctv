<x-layout title="Cotizaciones · CCTV Manager" active="cotizaciones">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Cotizaciones</h1>
            <p class="mt-1 text-slate-500">Módulo independiente. Crea y gestiona cotizaciones sin pasar por Proyectos.</p>
        </div>
        <a href="{{ route('quotations.create') }}" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
            Nueva Cotización
        </a>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Proyecto</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">IVA %</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($quotations as $quote)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $quote->code }}</td>
                        <td class="px-4 py-3">{{ $quote->project?->name }}</td>
                        <td class="px-4 py-3 capitalize">{{ $quote->status }}</td>
                        <td class="px-4 py-3 text-right">{{ $quote->total }}</td>
                        <td class="px-4 py-3">{{ $quote->vat_rate_percent }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="text-blue-600 hover:underline" href="{{ route('projects.quotations.show', [$quote->project_id, $quote->id]) }}">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Aún no hay cotizaciones.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
