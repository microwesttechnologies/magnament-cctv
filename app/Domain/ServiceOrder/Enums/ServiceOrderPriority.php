<?php

declare(strict_types=1);

namespace App\Domain\ServiceOrder\Enums;

use InvalidArgumentException;

enum ServiceOrderPriority: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';

    public function label(): string
    {
        return match ($this) {
            self::Baja => 'Baja',
            self::Media => 'Media',
            self::Alta => 'Alta',
        };
    }

    public static function fromString(string $value): self
    {
        $priority = self::tryFrom($value);
        if ($priority === null) {
            throw new InvalidArgumentException('Prioridad de orden no válida.');
        }

        return $priority;
    }
}
