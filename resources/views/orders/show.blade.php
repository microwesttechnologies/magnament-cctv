<x-layout title="Orden {{ $order->code }} · CCTV Manager" active="cotizaciones">
    <div>
        <p class="text-sm text-slate-500"><a href="{{ route('projects.show', $project) }}" class="hover:underline">{{ $project->name }}</a></p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight">{{ $order->code }}</h1>
        <p class="mt-1 text-slate-500 capitalize">Estado: {{ $order->status }} · Origen: cotización {{ $order->quotation?->code }}</p>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Orden de Instalación / Implementación</h2>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Proyecto</dt><dd class="font-medium">{{ $project->name }}</dd></div>
            <div><dt class="text-slate-500">Cotización</dt><dd class="font-medium">
                <a class="text-blue-600 hover:underline" href="{{ route('projects.quotations.show', [$project, $order->quotation_id]) }}">{{ $order->quotation?->code }}</a>
            </dd></div>
            <div><dt class="text-slate-500">Total cotizado</dt><dd class="font-medium">{{ $order->quotation?->total }}</dd></div>
            <div><dt class="text-slate-500">IVA aplicado</dt><dd class="font-medium">{{ $order->quotation?->vat_rate_percent }}%</dd></div>
        </dl>
        @if ($order->notes)
            <p class="mt-4 text-sm text-slate-700 whitespace-pre-wrap">{{ $order->notes }}</p>
        @endif
        <div class="mt-6">
            <a href="{{ route('trazabilidad', ['project_id' => $project->id]) }}" class="text-sm font-medium text-blue-600 hover:underline">Ver trazabilidad del proyecto</a>
        </div>
    </div>
</x-layout>
