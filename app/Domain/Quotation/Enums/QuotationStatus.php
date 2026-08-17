<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Enums;

enum QuotationStatus: string
{
    case Borrador = 'borrador';
    case Emitida = 'emitida';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Convertida = 'convertida';
    case Cancelada = 'cancelada';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Borrador => [self::Emitida, self::Cancelada],
            self::Emitida => [self::Aprobada, self::Rechazada, self::Cancelada],
            self::Aprobada => [self::Convertida],
            self::Rechazada, self::Convertida, self::Cancelada => [],
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Borrador;
    }

    public function canConvertToOrder(): bool
    {
        return $this === self::Aprobada;
    }

    public function freezesVatRate(): bool
    {
        return in_array($this, [self::Emitida, self::Aprobada, self::Convertida, self::Rechazada], true);
    }
}
