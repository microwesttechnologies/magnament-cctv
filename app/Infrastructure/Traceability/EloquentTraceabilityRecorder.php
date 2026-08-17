<?php

declare(strict_types=1);

namespace App\Infrastructure\Traceability;

use App\Domain\Quotation\Ports\TraceabilityRecorderInterface;
use App\Models\TraceabilityEvent;
use Illuminate\Support\Facades\Log;

final class EloquentTraceabilityRecorder implements TraceabilityRecorderInterface
{
    public function record(
        int $projectId,
        string $eventType,
        string $title,
        array $payload = [],
        ?int $quotationId = null,
        ?int $orderId = null,
        ?int $userId = null,
        ?int $serviceOrderId = null,
    ): void {
        if ($projectId < 1) {
            Log::warning('[EloquentTraceabilityRecorder] missing project_id', [
                'event_type' => $eventType,
            ]);

            return;
        }

        TraceabilityEvent::query()->create([
            'project_id' => $projectId,
            'quotation_id' => $quotationId,
            'order_id' => $orderId,
            'service_order_id' => $serviceOrderId,
            'event_type' => $eventType,
            'title' => $title,
            'payload' => $payload,
            'user_id' => $userId,
        ]);

        Log::info('[EloquentTraceabilityRecorder] recorded', [
            'event_type' => $eventType,
            'project_id' => $projectId,
            'quotation_id' => $quotationId,
            'order_id' => $orderId,
            'service_order_id' => $serviceOrderId,
        ]);
    }
}
