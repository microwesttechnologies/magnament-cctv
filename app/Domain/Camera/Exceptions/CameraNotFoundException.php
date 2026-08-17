<?php

declare(strict_types=1);

namespace App\Domain\Camera\Exceptions;

use App\Domain\Camera\ValueObjects\CameraId;
use RuntimeException;

final class CameraNotFoundException extends RuntimeException
{
    public static function withId(CameraId $id): self
    {
        return new self("No se encontró ninguna cámara con el id '{$id->value()}'.");
    }
}
