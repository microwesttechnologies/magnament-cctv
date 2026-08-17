<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Order\Repositories\InstallationOrderRepositoryInterface;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Models\InstallationOrder;

final class EloquentInstallationOrderRepository implements InstallationOrderRepositoryInterface
{
    public function existsForQuotation(QuotationId $quotationId): bool
    {
        return InstallationOrder::query()
            ->where('quotation_id', $quotationId->value())
            ->exists();
    }

    public function create(
        int $projectId,
        int $quotationId,
        string $code,
        string $status,
        ?string $notes,
    ): array {
        $order = InstallationOrder::query()->create([
            'project_id' => $projectId,
            'quotation_id' => $quotationId,
            'code' => $code,
            'status' => $status,
            'notes' => $notes,
        ]);

        return [
            'id' => (int) $order->id,
            'code' => $order->code,
        ];
    }

    public function nextCode(): string
    {
        $next = (int) InstallationOrder::query()->max('id') + 1;

        return 'ORD-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
