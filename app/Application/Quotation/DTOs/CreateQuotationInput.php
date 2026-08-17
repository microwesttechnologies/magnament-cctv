<?php

declare(strict_types=1);

namespace App\Application\Quotation\DTOs;

final class CreateQuotationInput
{
    /** @param list<QuotationLineInput> $lines */
    public function __construct(
        public readonly int $projectId,
        public readonly string $workDescription,
        public readonly array $lines,
        public readonly ?int $createdBy,
    ) {
    }
}
