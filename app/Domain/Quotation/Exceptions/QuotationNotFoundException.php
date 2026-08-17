<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Exceptions;

use DomainException;

final class QuotationNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Cotización no encontrada: {$id}.");
    }
}
