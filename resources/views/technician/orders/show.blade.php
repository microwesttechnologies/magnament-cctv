@php
    $canStart = $order->statusEnum()->canStart();
    $canResolve = $order->statusEnum()->canResolve();
    $canCancel = $order->statusEnum()->canCancel();
    $canEvidence = ! $order->statusEnum()->isTerminal();
    $hasPng = $order->evidences->contains(fn ($item) => $item->mime === 'image/png');
@endphp

<x-layout.technician :title="$order->code.' · Management CCTV'" active="orders">
    <a href="{{ route('technician.orders.index') }}" class="text-sm font-medium text-accent">← Mis órdenes</a>
    <p class="mt-3 font-mono text-sm font-semibold text-accent">{{ $order->code }}</p>
    <h1 class="mt-1 text-2xl font-bold">{{ $order->project?->name }}</h1>
    <p class="mt-2 text-sm text-foreground-muted">{{ $order->project?->address }} {{ $order->project?->city ? '· '.$order->project->city : '' }}</p>

    @if (session('status'))
        <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert variant="error" class="mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <div class="mt-4 rounded-xl border border-border bg-surface p-4">
        <p class="text-sm leading-relaxed">{{ $order->description }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <x-ui.badge :variant="$order->priority === 'alta' ? 'error' : ($order->priority === 'media' ? 'warning' : 'muted')">{{ $order->priorityEnum()->label() }}</x-ui.badge>
            <x-ui.badge :variant="$order->status === 'en_proceso' ? 'info' : ($order->status === 'resuelta' ? 'success' : ($order->status === 'cancelada' ? 'error' : 'warning'))">{{ $order->statusEnum()->label() }}</x-ui.badge>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-foreground-muted">Técnico</dt>
                <dd class="font-medium">{{ $order->technician?->name }}</dd>
            </div>
            <div>
                <dt class="text-foreground-muted">Fecha</dt>
                <dd>{{ $order->scheduled_at?->format('d/m/Y') ?? $order->created_at?->format('d/m/Y') }}</dd>
            </div>
        </dl>
        @if ($order->observations)
            <p class="mt-3 text-sm"><span class="font-medium">Observaciones:</span> {{ $order->observations }}</p>
        @endif
        @if ($order->resolution_notes)
            <p class="mt-3 text-sm"><span class="font-medium">Resolución:</span> {{ $order->resolution_notes }}</p>
        @endif
        @if ($order->cancellation_reason)
            <p class="mt-3 text-sm"><span class="font-medium">Cancelación:</span> {{ $order->cancellation_reason }}</p>
        @endif
    </div>

    @if ($canStart)
        <form method="POST" action="{{ route('technician.orders.start', $order) }}" class="mt-4">
            @csrf
            <x-ui.button type="submit" class="min-h-11 w-full justify-center">Iniciar orden</x-ui.button>
        </form>
    @endif

    @if ($canEvidence)
        <section class="mt-5 rounded-xl border border-border bg-surface p-4" x-data="evidenceCapture()">
            <h2 class="text-base font-semibold">Evidencia PNG</h2>
            <p class="mt-1 text-xs text-foreground-muted">Toma una foto o elige una imagen. Se convierte a PNG antes de subir.</p>
            <form method="POST" action="{{ route('technician.orders.evidence', $order) }}" enctype="multipart/form-data" class="mt-3 space-y-3" @submit="prepareSubmit($event)">
                @csrf
                <input type="file" name="evidence" accept="image/png,image/jpeg,image/jpg,image/*" capture="environment" class="hidden" x-ref="file" @change="preview($event)">
                <x-ui.button type="button" variant="secondary" class="min-h-11 w-full justify-center" @click="$refs.file.click()">Tomar o elegir foto</x-ui.button>
                <img x-show="previewUrl" :src="previewUrl" alt="Vista previa" class="max-h-56 w-full rounded-lg object-contain" x-cloak>
                <x-ui.form-field label="Descripción (opcional)" for="evidence_description">
                    <x-ui.input id="evidence_description" name="description" class="min-h-11" />
                </x-ui.form-field>
                <div x-show="uploading" class="h-2 overflow-hidden rounded-full bg-muted">
                    <div class="h-full w-2/3 animate-pulse bg-accent motion-reduce:animate-none"></div>
                </div>
                <x-ui.button type="submit" class="min-h-11 w-full justify-center" :disabled="false">Subir evidencia</x-ui.button>
            </form>
        </section>
    @endif

    @if ($order->evidences->isNotEmpty())
        <ul class="mt-4 grid grid-cols-2 gap-2">
            @foreach ($order->evidences as $evidence)
                <li class="overflow-hidden rounded-lg border border-border">
                    <img src="{{ $evidence->url() }}" alt="Evidencia" class="h-28 w-full object-cover">
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canResolve)
        <section class="mt-5 rounded-xl border border-border bg-surface p-4">
            <h2 class="text-base font-semibold">Resolver orden</h2>
            @unless ($hasPng)
                <p class="mt-2 text-sm text-destructive">Debes subir al menos un PNG antes de resolver.</p>
            @endunless
            <form method="POST" action="{{ route('technician.orders.resolve', $order) }}" class="mt-3 space-y-3" onsubmit="return confirm('¿Confirmas que la orden quedó resuelta?')">
                @csrf
                <x-ui.form-field label="Observación de resolución" for="resolution_notes" required>
                    <x-ui.textarea id="resolution_notes" name="resolution_notes" rows="3" required>{{ old('resolution_notes') }}</x-ui.textarea>
                </x-ui.form-field>
                <x-ui.button type="submit" variant="success" class="min-h-11 w-full justify-center" :disabled="! $hasPng">Resolver orden</x-ui.button>
            </form>
        </section>
    @endif

    @if ($canCancel && $order->status === 'en_proceso')
        <section class="mt-5 rounded-xl border border-destructive/30 bg-surface p-4">
            <h2 class="text-base font-semibold">Cancelar orden</h2>
            @unless ($hasPng)
                <p class="mt-2 text-sm text-destructive">Debes subir evidencia PNG antes de cancelar.</p>
            @endunless
            <form method="POST" action="{{ route('technician.orders.cancel', $order) }}" class="mt-3 space-y-3" onsubmit="return confirm('¿Confirmas la cancelación?')">
                @csrf
                <x-ui.form-field label="Motivo de cancelación" for="cancellation_reason" required>
                    <x-ui.textarea id="cancellation_reason" name="cancellation_reason" rows="3" required>{{ old('cancellation_reason') }}</x-ui.textarea>
                </x-ui.form-field>
                <x-ui.button type="submit" variant="destructive" class="min-h-11 w-full justify-center" :disabled="! $hasPng">Confirmar cancelación</x-ui.button>
            </form>
        </section>
    @endif
</x-layout.technician>
