<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Quotation\Ports\AuditLoggerInterface;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

final class EloquentAuditLogger implements AuditLoggerInterface
{
    public function record(
        string $auditableType,
        int $auditableId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): void {
        AuditLog::query()->create([
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
        ]);

        Log::info('[EloquentAuditLogger] recorded', [
            'action' => $action,
            'auditable_id' => $auditableId,
        ]);
    }
}
