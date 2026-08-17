<?php

declare(strict_types=1);

namespace App\Domain\Camera\ValueObjects;

use InvalidArgumentException;

final class IpAddress
{
    private function __construct(private readonly string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException("La dirección IP '{$value}' no es válida.");
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
