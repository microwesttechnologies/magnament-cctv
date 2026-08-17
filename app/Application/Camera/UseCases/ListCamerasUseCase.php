<?php

declare(strict_types=1);

namespace App\Application\Camera\UseCases;

use App\Domain\Camera\Entities\Camera;
use App\Domain\Camera\Repositories\CameraRepositoryInterface;

final class ListCamerasUseCase
{
    public function __construct(
        private readonly CameraRepositoryInterface $cameras,
    ) {
    }

    /**
     * @return Camera[]
     */
    public function execute(): array
    {
        return $this->cameras->all();
    }
}
