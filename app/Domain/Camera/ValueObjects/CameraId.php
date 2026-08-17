<?php

declare(strict_types=1);

namespace App\Domain\Camera\ValueObjects;

use InvalidArgumentException;

final class CameraId
{
    private function __construct(private readonly string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('El identificador de la cámara no puede estar vacío.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
