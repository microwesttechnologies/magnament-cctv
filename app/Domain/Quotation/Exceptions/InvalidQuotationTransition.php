<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Exceptions;

use DomainException;

final class InvalidQuotationTransition extends DomainException
{
    public static function fromTo(string $from, string $to): self
    {
        return new self("Transición de cotización no permitida: {$from} → {$to}.");
    }
}
