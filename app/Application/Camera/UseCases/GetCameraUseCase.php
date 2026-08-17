<?php

declare(strict_types=1);

namespace App\Application\Camera\UseCases;

use App\Domain\Camera\Entities\Camera;
use App\Domain\Camera\Exceptions\CameraNotFoundException;
use App\Domain\Camera\Repositories\CameraRepositoryInterface;
use App\Domain\Camera\ValueObjects\CameraId;

final class GetCameraUseCase
{
    public function __construct(
        private readonly CameraRepositoryInterface $cameras,
    ) {
    }

    public function execute(string $id): Camera
    {
        $cameraId = CameraId::fromString($id);

        return $this->cameras->findById($cameraId)
            ?? throw CameraNotFoundException::withId($cameraId);
    }
}
