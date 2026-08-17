@php
    $canStart = $order->statusEnum()->canStart();
    $canFinalize = $order->statusEnum()->canFinalize();
    $isTerminal = $order->statusEnum()->isTerminal();
    $statusVariant = match ($order->status) {
        'en_proceso' => 'info',
        'resuelta' => 'success',
        'no_resuelta' => 'warning',
        'cancelada' => 'error',
        default => 'warning',
    };
    $evidencesPayload = $order->evidences
        ->map(fn ($item) => ['id' => $item->id, 'url' => $item->url()])
        ->values()
        ->all();
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
            <x-ui.badge :variant="$statusVariant">{{ $order->statusEnum()->label() }}</x-ui.badge>
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
        @if ($order->unresolved_notes)
            <p class="mt-3 text-sm"><span class="font-medium">No resuelta:</span> {{ $order->unresolved_notes }}</p>
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

    @if ($canFinalize)
        <section
            class="mt-5 space-y-4"
            x-data="orderCompletionFlow({{ \Illuminate\Support\Js::from([
                'evidences' => $evidencesPayload,
                'uploadUrl' => route('technician.orders.evidence', $order),
                'deleteUrlTemplate' => route('technician.orders.evidence.destroy', [$order, '__EVIDENCE__']),
                'finalizeUrl' => route('technician.orders.finalize', $order),
                'csrf' => csrf_token(),
                'oldResult' => old('result'),
                'oldObservation' => old('observation'),
            ]) }})"
        >
            <div
                class="rounded-xl border border-border bg-surface p-4"
                x-show="true"
                x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                <h2 class="text-base font-semibold">Resultado de la orden</h2>
                <x-ui.form-field label="¿Cuál fue el resultado?" for="order_result" class="mt-3">
                    <x-ui.select id="order_result" name="result" x-model="result" class="min-h-11 w-full">
                        <option value="">Seleccionar resultado...</option>
                        <option value="resuelta">Resuelta</option>
                        <option value="no_resuelta">No resuelta</option>
                        <option value="cancelada">Cancelada</option>
                    </x-ui.select>
                </x-ui.form-field>
            </div>

            <div
                x-show="showFinalizeCard"
                x-cloak
                x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="rounded-xl border border-border bg-surface p-4"
            >
                <h2 class="text-base font-semibold">Finalizar orden</h2>

                <div class="mt-4 space-y-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Resultado</p>
                    <p class="text-sm font-medium text-foreground" x-text="resultLabel"></p>
                </div>

                <div class="mt-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-foreground-muted">Evidencias fotográficas</p>
                    <p class="mt-1 text-xs text-foreground-muted">Agrega al menos una fotografía antes de finalizar.</p>

                    <ul class="mt-3 grid grid-cols-3 gap-2">
                        <template x-for="item in evidences" :key="item.id">
                            <li class="relative overflow-hidden rounded-lg border border-border">
                                <img :src="item.url" alt="Evidencia" class="h-24 w-full object-cover">
                                <button
                                    type="button"
                                    class="absolute right-1 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-foreground/70 text-xs font-bold text-surface"
                                    @click="removeEvidence(item.id)"
                                    aria-label="Eliminar evidencia"
                                >&times;</button>
                            </li>
                        </template>
                    </ul>

                    <input type="file" accept="image/png,image/jpeg,image/jpg,image/*" capture="environment" class="hidden" x-ref="photoInput" @change="addPhoto($event)">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        class="mt-3 min-h-11 w-full justify-center"
                        @click="$refs.photoInput.click()"
                        :disabled="false"
                    >+ Agregar fotografía</x-ui.button>

                    <p x-show="uploading" x-cloak class="mt-2 text-sm text-foreground-muted">Subiendo...</p>
                    <p x-show="uploadSuccess" x-cloak class="mt-2 text-sm text-success">✓ Evidencia agregada</p>
                    <p x-show="uploadError" x-cloak class="mt-2 text-sm text-destructive" x-text="uploadError"></p>
                    <p x-show="evidenceError" x-cloak class="mt-2 text-sm text-destructive" x-text="evidenceError"></p>
                </div>

                <form method="POST" :action="config.finalizeUrl" class="mt-5 space-y-4" @submit="submitFinalize($event)">
                    @csrf
                    <input type="hidden" name="result" :value="result">

                    <x-ui.form-field label="Observación" for="observation" required>
                        <x-ui.textarea
                            id="observation"
                            name="observation"
                            rows="4"
                            x-model="observation"
                            placeholder="Describe brevemente el trabajo realizado, resultado encontrado o motivo de la cancelación."
                            required
                        >{{ old('observation') }}</x-ui.textarea>
                    </x-ui.form-field>
                    <p x-show="observationError" x-cloak class="text-sm text-destructive" x-text="observationError"></p>

                    <button
                        type="submit"
                        class="ui-btn-base ui-btn-md min-h-11 w-full justify-center"
                        :class="{
                            'ui-btn-success': result === 'resuelta',
                            'ui-btn-destructive': result === 'cancelada',
                            'ui-btn-primary': result === 'no_resuelta',
                        }"
                        :disabled="submitting"
                        x-text="submitLabel"
                    ></button>
                </form>
            </div>
        </section>
    @endif

    @if ($isTerminal && $order->evidences->isNotEmpty())
        <section class="mt-5">
            <h2 class="text-base font-semibold">Evidencias</h2>
            <ul class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($order->evidences as $evidence)
                    <li class="overflow-hidden rounded-lg border border-border">
                        <img src="{{ $evidence->url() }}" alt="Evidencia" class="h-28 w-full object-cover">
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layout.technician>
