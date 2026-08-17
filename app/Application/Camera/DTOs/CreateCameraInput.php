<?php

declare(strict_types=1);

namespace App\Application\Camera\DTOs;

/**
 * Objeto de transferencia de datos: transporta la entrada de forma neutra
 * desde la capa HTTP hacia el caso de uso, sin acoplar Request de Laravel.
 */
final class CreateCameraInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $location,
        public readonly string $ipAddress,
    ) {
    }
}
