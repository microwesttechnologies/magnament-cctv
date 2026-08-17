<?php

declare(strict_types=1);

namespace App\Domain\Quotation\ValueObjects;

use InvalidArgumentException;

final class VatRate
{
    private function __construct(private readonly string $percent)
    {
    }

    public static function fromString(string $percent): self
    {
        if (! is_numeric($percent)) {
            throw new InvalidArgumentException('VAT rate must be numeric.');
        }

        if (bccomp($percent, '0', 4) < 0) {
            throw new InvalidArgumentException('VAT rate cannot be negative.');
        }

        return new self(bcadd($percent, '0', 4));
    }

    public function percent(): string
    {
        return $this->percent;
    }
}
