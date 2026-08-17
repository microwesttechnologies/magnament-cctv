@php
    $statusVariant = [
        'pendiente' => 'muted',
        'asignada' => 'warning',
        'en_proceso' => 'info',
        'resuelta' => 'success',
        'cancelada' => 'error',
    ];
    $priorityVariant = [
        'baja' => 'muted',
        'media' => 'warning',
        'alta' => 'error',
    ];
    $canAssign = $order->statusEnum()->canAssign();
    $canReassign = $order->statusEnum()->canReassign();
    $canCancel = $order->statusEnum()->canCancel();
    $canPriority = ! $order->statusEnum()->isTerminal();
@endphp

<x-layout :title="$order->code.' · CCTV Manager'" active="ordenes">
    <x-ui.page-header
        :title="$order->code"
        :description="$order->description"
    >
        <x-slot:actions>
            <x-ui.badge :variant="$statusVariant[$order->status] ?? 'muted'" dot>{{ $order->statusEnum()->label() }}</x-ui.badge>
            <x-ui.badge :variant="$priorityVariant[$order->priority] ?? 'muted'">{{ $order->priorityEnum()->label() }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert variant="error" class="mb-6">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Detalle">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Proyecto</dt>
                        <dd class="mt-1 font-medium">{{ $order->project?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Ubicación</dt>
                        <dd class="mt-1">{{ $order->project?->address ?? '—' }} {{ $order->project?->city ? '· '.$order->project->city : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Técnico</dt>
                        <dd class="mt-1">{{ $order->technician?->name ?? 'Sin asignar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Fecha programada</dt>
                        <dd class="mt-1">{{ $order->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Creada</dt>
                        <dd class="mt-1">{{ $order->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Asignada</dt>
                        <dd class="mt-1">{{ $order->assigned_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Inicio</dt>
                        <dd class="mt-1">{{ $order->started_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Resolución / cancelación</dt>
                        <dd class="mt-1">{{ $order->resolved_at?->format('d/m/Y H:i') ?? $order->cancelled_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Solicitante</dt>
                        <dd class="mt-1">{{ $order->requester_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-foreground-muted">Teléfono solicitante</dt>
                        <dd class="mt-1">{{ $order->requester_phone ?? '—' }}</dd>
                    </div>
                </dl>
                @if ($order->observations)
                    <p class="mt-4 text-sm text-foreground-muted"><span class="font-medium text-foreground">Observaciones:</span> {{ $order->observations }}</p>
                @endif
                @if ($order->resolution_notes)
                    <p class="mt-2 text-sm"><span class="font-medium">Resolución:</span> {{ $order->resolution_notes }}</p>
                @endif
                @if ($order->cancellation_reason)
                    <p class="mt-2 text-sm"><span class="font-medium">Cancelación:</span> {{ $order->cancellation_reason }}</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Evidencias PNG">
                @if ($order->evidences->isEmpty())
                    <x-ui.empty-state title="Sin evidencias" description="El técnico debe adjuntar al menos un PNG para resolver o cancelar una orden en proceso." />
                @else
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($order->evidences as $evidence)
                            <li class="overflow-hidden rounded-lg border border-border">
                                <img src="{{ $evidence->url() }}" alt="{{ $evidence->original_name ?? 'Evidencia' }}" class="h-32 w-full object-cover">
                                <p class="truncate px-2 py-1 text-xs text-foreground-muted">{{ $evidence->created_at?->format('d/m/Y H:i') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @if ($canAssign)
                <x-ui.card title="Asignar">
                    <form method="POST" action="{{ route('service-orders.assign', $order) }}" class="space-y-3">
                        @csrf
                        <x-ui.form-field label="Técnico" for="assign_staff_id" required>
                            <x-ui.select id="assign_staff_id" name="staff_id" required>
                                <option value="">Selecciona</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.form-field>
                        <x-ui.button type="submit" class="w-full justify-center">Asignar</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canReassign)
                <x-ui.card title="Reasignar">
                    <p class="mb-3 text-sm text-foreground-muted">Técnico actual: {{ $order->technician?->name }}</p>
                    <form method="POST" action="{{ route('service-orders.reassign', $order) }}" class="space-y-3">
                        @csrf
                        <x-ui.form-field label="Nuevo técnico" for="reassign_staff_id" required>
                            <x-ui.select id="reassign_staff_id" name="staff_id" required>
                                <option value="">Selecciona</option>
                                @foreach ($technicians as $technician)
                                    @if ((int) $technician->id !== (int) $order->staff_id)
                                        <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                    @endif
                                @endforeach
                            </x-ui.select>
                        </x-ui.form-field>
                        <x-ui.form-field label="Motivo (opcional)" for="reason">
                            <x-ui.textarea id="reason" name="reason" rows="2">{{ old('reason') }}</x-ui.textarea>
                        </x-ui.form-field>
                        <x-ui.button type="submit" variant="secondary" class="w-full justify-center">Reasignar</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canPriority)
                <x-ui.card title="Prioridad">
                    <form method="POST" action="{{ route('service-orders.priority', $order) }}" class="space-y-3">
                        @csrf
                        <x-ui.select name="priority">
                            @foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $value => $label)
                                <option value="{{ $value }}" @selected($order->priority === $value)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.button type="submit" variant="outline" class="w-full justify-center">Actualizar</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canCancel)
                <x-ui.card title="Cancelar orden">
                    <p class="mb-3 text-xs text-foreground-muted">
                        @if ($order->statusEnum()->requiresEvidenceToClose())
                            En proceso exige evidencia PNG antes de cancelar.
                        @else
                            Indica el motivo. Si está en proceso, también se exige PNG.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('service-orders.cancel', $order) }}" class="space-y-3" onsubmit="return confirm('¿Confirmas la cancelación?')">
                        @csrf
                        <x-ui.form-field label="Motivo" for="cancellation_reason" required>
                            <x-ui.textarea id="cancellation_reason" name="cancellation_reason" rows="3" required>{{ old('cancellation_reason') }}</x-ui.textarea>
                        </x-ui.form-field>
                        <x-ui.button type="submit" variant="destructive" class="w-full justify-center">Cancelar orden</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card title="Historial">
                @if ($history->isEmpty())
                    <x-ui.empty-state title="Sin eventos" description="La trazabilidad de esta orden aparecerá aquí." />
                @else
                    <ol class="space-y-3">
                        @foreach ($history as $event)
                            <li class="border-b border-border-subtle pb-3 last:border-0 last:pb-0">
                                <p class="text-sm font-medium">{{ $event->title }}</p>
                                <p class="mt-0.5 text-xs text-foreground-muted">
                                    {{ $event->user?->name ?? 'Sistema' }}
                                    · {{ $event->created_at?->format('d/m/Y H:i') }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-layout>
