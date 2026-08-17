<?php

declare(strict_types=1);

namespace App\Domain\ServiceOrder\Enums;

use InvalidArgumentException;

enum ServiceOrderStatus: string
{
    case Pendiente = 'pendiente';
    case Asignada = 'asignada';
    case EnProceso = 'en_proceso';
    case Resuelta = 'resuelta';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Asignada => 'Asignada',
            self::EnProceso => 'En proceso',
            self::Resuelta => 'Resuelta',
            self::Cancelada => 'Cancelada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pendiente, self::Asignada, self::EnProceso], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Resuelta, self::Cancelada], true);
    }

    public function canAssign(): bool
    {
        return $this === self::Pendiente;
    }

    public function canReassign(): bool
    {
        return in_array($this, [self::Asignada, self::EnProceso], true);
    }

    public function canStart(): bool
    {
        return $this === self::Asignada;
    }

    public function canResolve(): bool
    {
        return $this === self::EnProceso;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Pendiente, self::Asignada, self::EnProceso], true);
    }

    public function requiresEvidenceToClose(): bool
    {
        return $this === self::EnProceso;
    }

    public static function fromString(string $value): self
    {
        $status = self::tryFrom($value);
        if ($status === null) {
            throw new InvalidArgumentException('Estado de orden no válido.');
        }

        return $status;
    }
}
