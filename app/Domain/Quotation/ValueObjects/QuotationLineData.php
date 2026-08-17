<?php

declare(strict_types=1);

namespace App\Domain\Quotation\ValueObjects;

use InvalidArgumentException;

final class QuotationLineData
{
    public function __construct(
        private readonly string $productName,
        private readonly string $quantity,
        private readonly ?string $brand,
        private readonly ?string $serial,
        private readonly Money $unitPrice,
        private readonly int $sortOrder,
    ) {
        if (trim($productName) === '') {
            throw new InvalidArgumentException('Product name is required.');
        }

        if (! is_numeric($quantity) || bccomp($quantity, '0', 2) <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (bccomp($unitPrice->amount(), '0', 2) < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative.');
        }
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function quantity(): string
    {
        return bcadd($this->quantity, '0', 2);
    }

    public function brand(): ?string
    {
        return $this->brand;
    }

    public function serial(): ?string
    {
        return $this->serial;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function lineSubtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity());
    }
}
