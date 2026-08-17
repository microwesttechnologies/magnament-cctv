<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories;

use App\Domain\Quotation\ValueObjects\QuotationId;

interface InstallationOrderRepositoryInterface
{
    public function existsForQuotation(QuotationId $quotationId): bool;

    /**
     * @return array{id: int, code: string}
     */
    public function create(
        int $projectId,
        int $quotationId,
        string $code,
        string $status,
        ?string $notes,
    ): array;

    public function nextCode(): string;
}
