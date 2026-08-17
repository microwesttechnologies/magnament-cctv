<?php

declare(strict_types=1);

namespace App\Domain\ServiceOrder\Exceptions;

use RuntimeException;

final class InvalidServiceOrderTransition extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
