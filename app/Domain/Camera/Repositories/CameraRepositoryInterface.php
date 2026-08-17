<?php

declare(strict_types=1);

namespace App\Domain\Camera\Repositories;

use App\Domain\Camera\Entities\Camera;
use App\Domain\Camera\ValueObjects\CameraId;

/**
 * Contrato del repositorio. Vive en el dominio; la infraestructura lo implementa.
 * Así el dominio no depende de Eloquent (inversión de dependencias).
 */
interface CameraRepositoryInterface
{
    public function save(Camera $camera): void;

    public function findById(CameraId $id): ?Camera;

    /**
     * @return Camera[]
     */
    public function all(): array;

    public function delete(CameraId $id): void;
}
