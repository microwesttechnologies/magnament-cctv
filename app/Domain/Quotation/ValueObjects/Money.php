<?php

declare(strict_types=1);

namespace App\Domain\Quotation\ValueObjects;

use InvalidArgumentException;

final class Money
{
    private function __construct(private readonly string $amount)
    {
    }

    public static function fromString(string $amount): self
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Money amount must be numeric.');
        }

        return new self(bcadd($amount, '0', 2));
    }

    public static function zero(): self
    {
        return new self('0.00');
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function add(self $other): self
    {
        return self::fromString(bcadd($this->amount, $other->amount, 2));
    }

    public function multiply(string $factor): self
    {
        return self::fromString(bcmul($this->amount, $factor, 2));
    }

    public function percentageOf(string $percent): self
    {
        $factor = bcdiv($percent, '100', 6);

        return self::fromString(bcmul($this->amount, $factor, 2));
    }
}
