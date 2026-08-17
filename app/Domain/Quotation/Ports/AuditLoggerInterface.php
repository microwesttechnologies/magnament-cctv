<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Ports;

interface AuditLoggerInterface
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        string $auditableType,
        int $auditableId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): void;
}
