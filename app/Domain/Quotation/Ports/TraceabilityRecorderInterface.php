<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Ports;

interface TraceabilityRecorderInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        int $projectId,
        string $eventType,
        string $title,
        array $payload = [],
        ?int $quotationId = null,
        ?int $orderId = null,
        ?int $userId = null,
        ?int $serviceOrderId = null,
    ): void;
}
