<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Exceptions;

use DomainException;

final class QuotationNotConvertible extends DomainException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
