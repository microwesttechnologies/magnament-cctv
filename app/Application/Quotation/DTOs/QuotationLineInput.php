<?php

declare(strict_types=1);

namespace App\Application\Quotation\DTOs;

final class QuotationLineInput
{
    public function __construct(
        public readonly string $productName,
        public readonly string $quantity,
        public readonly ?string $brand,
        public readonly ?string $serial,
        public readonly string $unitPrice,
        public readonly int $sortOrder = 0,
    ) {
    }
}
