<x-layout title="Cotización {{ $quotation->code() }} · CCTV Manager" active="cotizaciones">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500"><a href="{{ route('projects.show', $project) }}" class="hover:underline">{{ $project->name }}</a></p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight">{{ $quotation->code() }}</h1>
            <p class="mt-1 capitalize text-slate-500">Estado: <strong>{{ $quotation->status()->value }}</strong> · IVA snapshot: {{ $quotation->vatRate()->percent() }}%</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('projects.quotations.pdf', [$project, $quotation->id()->value()]) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">Descargar PDF</a>
            @if ($quotation->status()->isEditable())
                <a href="{{ route('projects.quotations.edit', [$project, $quotation->id()->value()]) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">Editar</a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Descripción del trabajo</h2>
                <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ $quotation->workDescription() }}</p>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Marca</th>
                            <th class="px-4 py-3">Serie</th>
                            <th class="px-4 py-3 text-right">Cant.</th>
                            <th class="px-4 py-3 text-right">P. unit.</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($quotation->lines() as $line)
                            <tr>
                                <td class="px-4 py-3">{{ $line->productName() }}</td>
                                <td class="px-4 py-3">{{ $line->brand() ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $line->serial() ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $line->quantity() }}</td>
                                <td class="px-4 py-3 text-right">{{ $line->unitPrice()->amount() }}</td>
                                <td class="px-4 py-3 text-right">{{ $line->lineSubtotal()->amount() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Historial</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @forelse ($history as $entry)
                        <li class="border-b border-slate-100 pb-3">
                            <div class="font-medium">{{ $entry->action }}</div>
                            <div class="text-xs text-slate-500">{{ $entry->created_at }}</div>
                        </li>
                    @empty
                        <li class="text-slate-500">Sin eventos de auditoría.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Totales</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ $quotation->subtotal()->amount() }}</dd></div>
                    <div class="flex justify-between"><dt>IVA ({{ $quotation->vatRate()->percent() }}%)</dt><dd>{{ $quotation->vatAmount()->amount() }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ $quotation->total()->amount() }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 space-y-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Acciones de estado</h2>

                @foreach ($quotation->status()->allowedTransitions() as $next)
                    @if ($next->value !== 'convertida')
                        <form method="POST" action="{{ route('projects.quotations.transition', [$project, $quotation->id()->value()]) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button type="submit" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium capitalize hover:bg-slate-50">
                                Pasar a {{ $next->value }}
                            </button>
                        </form>
                    @endif
                @endforeach

                @if ($quotation->status()->canConvertToOrder() && ! $model->installationOrder)
                    <form method="POST" action="{{ route('projects.quotations.convert', [$project, $quotation->id()->value()]) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">
                            Convertir a Orden de Instalación
                        </button>
                    </form>
                @endif

                @if ($model->installationOrder)
                    <a href="{{ route('projects.orders.show', [$project, $model->installationOrder->id]) }}" class="block w-full rounded-lg bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white">
                        Ver orden {{ $model->installationOrder->code }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-layout>
