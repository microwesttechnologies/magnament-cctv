<?php

declare(strict_types=1);

namespace App\Application\ServiceOrder;

use App\Domain\Quotation\Ports\TraceabilityRecorderInterface;
use App\Domain\ServiceOrder\Enums\ServiceOrderPriority;
use App\Domain\ServiceOrder\Ports\PushNotifierInterface;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderEvidence;
use App\Models\Staff;
use App\Support\Cache\CacheInvalidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ServiceOrderWorkflow
{
    public function __construct(
        private readonly TraceabilityRecorderInterface $traceability,
        private readonly PushNotifierInterface $notifier,
    ) {
    }

    /**
     * @param  array{
     *     project_id: int,
     *     description: string,
     *     priority: string,
     *     staff_id?: int|null,
     *     scheduled_at?: string|null,
     *     observations?: string|null,
     *     requester_name: string,
     *     requester_phone: string,
     *     dvr_id?: int|null,
     *     created_by?: int|null
     * }  $input
     */
    public function create(array $input): ServiceOrder
    {
        $priority = ServiceOrderPriority::fromString($input['priority']);
        $staffId = isset($input['staff_id']) ? (int) $input['staff_id'] : 0;
        $staffId = $staffId > 0 ? $staffId : null;

        $order = ServiceOrder::query()->create([
            'code' => ServiceOrder::nextCode(),
            'project_id' => (int) $input['project_id'],
            'dvr_id' => isset($input['dvr_id']) ? (int) $input['dvr_id'] : null,
            'staff_id' => $staffId,
            'created_by' => $input['created_by'] ?? null,
            'description' => $input['description'],
            'observations' => $input['observations'] ?? null,
            'requester_name' => $input['requester_name'],
            'requester_phone' => $input['requester_phone'],
            'priority' => $priority->value,
            'status' => $staffId ? 'asignada' : 'pendiente',
            'scheduled_at' => $input['scheduled_at'] ?? null,
            'assigned_at' => $staffId ? now() : null,
        ]);

        $this->trace($order, 'service_order.created', 'Orden de servicio creada: '.$order->code, [
            'staff_id' => $staffId,
            'priority' => $priority->value,
        ], $input['created_by'] ?? null);

        if ($staffId) {
            $this->trace($order, 'service_order.assigned', 'Orden asignada: '.$order->code, [
                'staff_id' => $staffId,
            ], $input['created_by'] ?? null);
            $this->notifyAssigned($order, 'assigned');
        }

        CacheInvalidator::dashboard();
        Log::info('[ServiceOrderWorkflow] created', ['code' => $order->code]);

        return $order;
    }

    public function assign(ServiceOrder $order, int $staffId, ?int $userId, ?string $reason = null): ServiceOrder
    {
        $order->assignTo($staffId);
        $this->trace($order, 'service_order.assigned', 'Orden asignada: '.$order->code, [
            'staff_id' => $staffId,
            'reason' => $reason,
        ], $userId);
        $this->notifyAssigned($order, 'assigned');
        CacheInvalidator::dashboard();

        return $order->fresh(['project', 'technician']) ?? $order;
    }

    public function reassign(ServiceOrder $order, int $staffId, ?int $userId, ?string $reason = null): ServiceOrder
    {
        $previousId = $order->reassignTo($staffId);
        $this->trace($order, 'service_order.reassigned', 'Orden reasignada: '.$order->code, [
            'previous_staff_id' => $previousId,
            'staff_id' => $staffId,
            'reason' => $reason,
        ], $userId);

        $this->notifyPreviousLost($previousId, $order);
        $this->notifyAssigned($order, 'reassigned');
        CacheInvalidator::dashboard();

        return $order->fresh(['project', 'technician']) ?? $order;
    }

    public function start(ServiceOrder $order, ?int $userId): ServiceOrder
    {
        $order->start();
        $this->trace($order, 'service_order.started', 'Orden iniciada: '.$order->code, [
            'staff_id' => $order->staff_id,
        ], $userId);
        CacheInvalidator::dashboard();

        return $order;
    }

    public function addEvidence(
        ServiceOrder $order,
        UploadedFile $file,
        ?int $userId,
        ?int $staffId,
        ?string $description = null,
    ): ServiceOrderEvidence {
        $path = $file->store('service_order_evidences', 'public');
        $stored = Storage::disk('public')->path($path);
        $mime = is_string($stored) && is_file($stored)
            ? ((new \finfo(FILEINFO_MIME_TYPE))->file($stored) ?: 'image/png')
            : 'image/png';

        $evidence = $order->evidences()->create([
            'uploaded_by' => $userId,
            'staff_id' => $staffId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'description' => $description,
        ]);

        Log::info('[ServiceOrderWorkflow.addEvidence] stored', [
            'code' => $order->code,
            'mime' => $mime,
            'evidence_id' => $evidence->id,
        ]);

        $this->trace($order, 'service_order.evidence_added', 'Evidencia agregada a '.$order->code, [
            'evidence_id' => $evidence->id,
        ], $userId);

        return $evidence;
    }

    public function resolve(ServiceOrder $order, string $notes, ?int $userId): ServiceOrder
    {
        $previousStatus = $order->status;
        $order->resolve($notes);
        $this->trace($order, 'service_order.resolved', 'Orden resuelta: '.$order->code, [
            'staff_id' => $order->staff_id,
            'previous_status' => $previousStatus,
            'new_status' => $order->status,
        ], $userId);
        CacheInvalidator::dashboard();

        return $order;
    }

    public function markUnresolved(ServiceOrder $order, string $notes, ?int $userId): ServiceOrder
    {
        $previousStatus = $order->status;
        $order->markUnresolved($notes);
        $this->trace($order, 'service_order.unresolved', 'Orden no resuelta: '.$order->code, [
            'staff_id' => $order->staff_id,
            'previous_status' => $previousStatus,
            'new_status' => $order->status,
        ], $userId);
        CacheInvalidator::dashboard();

        return $order;
    }

    public function cancel(ServiceOrder $order, string $reason, ?int $userId): ServiceOrder
    {
        $previousStatus = $order->status;
        $order->cancel($reason);
        $this->trace($order, 'service_order.cancelled', 'Orden cancelada: '.$order->code, [
            'reason' => $reason,
            'staff_id' => $order->staff_id,
            'previous_status' => $previousStatus,
            'new_status' => $order->status,
        ], $userId);

        if ($order->staff_id) {
            $staff = Staff::query()->find($order->staff_id);
            if ($staff?->user_id) {
                $this->notifier->notifyTechnician(
                    userId: (int) $staff->user_id,
                    type: 'cancelled',
                    title: 'Orden cancelada',
                    body: $order->code.' · '.$order->description,
                    serviceOrderId: (int) $order->id,
                    url: route('technician.orders.show', $order),
                );
            }
        }

        CacheInvalidator::dashboard();

        return $order;
    }

    public function updatePriority(ServiceOrder $order, string $priority, ?int $userId): ServiceOrder
    {
        $order->priority = ServiceOrderPriority::fromString($priority)->value;
        $order->save();
        $this->trace($order, 'service_order.updated', 'Prioridad actualizada: '.$order->code, [
            'priority' => $order->priority,
        ], $userId);
        $this->notifyAssigned($order, 'updated');

        return $order;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function trace(ServiceOrder $order, string $type, string $title, array $payload, ?int $userId): void
    {
        $this->traceability->record(
            projectId: (int) $order->project_id,
            eventType: $type,
            title: $title,
            payload: $payload,
            userId: $userId,
            serviceOrderId: (int) $order->id,
        );
    }

    private function notifyAssigned(ServiceOrder $order, string $type): void
    {
        $order->loadMissing(['project', 'technician.user']);
        $userId = $order->technician?->user_id;
        if (! $userId) {
            return;
        }

        $title = match ($type) {
            'reassigned' => 'Trabajo reasignado',
            'updated' => 'Orden actualizada',
            default => 'Nuevo trabajo asignado',
        };

        $this->notifier->notifyTechnician(
            userId: (int) $userId,
            type: $type,
            title: $title,
            body: $order->code.' · '.($order->project?->name ?? 'Proyecto').' · '.$order->description,
            serviceOrderId: (int) $order->id,
            url: route('technician.orders.show', $order),
        );
    }

    private function notifyPreviousLost(int $previousStaffId, ServiceOrder $order): void
    {
        $staff = Staff::query()->find($previousStaffId);
        if (! $staff?->user_id) {
            return;
        }

        $this->notifier->notifyTechnician(
            userId: (int) $staff->user_id,
            type: 'reassigned',
            title: 'Orden reasignada',
            body: $order->code.' ya no está asignada a ti.',
            serviceOrderId: (int) $order->id,
            url: route('technician.home'),
        );
    }
}
