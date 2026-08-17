<?php

declare(strict_types=1);

namespace App\Domain\Quotation\ValueObjects;

use InvalidArgumentException;

final class QuotationId
{
    private function __construct(private readonly int $value)
    {
    }

    public static function fromInt(int $value): self
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Quotation id must be positive.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
