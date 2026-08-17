<?php

declare(strict_types=1);

namespace App\Application\Camera\UseCases;

use App\Application\Camera\DTOs\CreateCameraInput;
use App\Domain\Camera\Entities\Camera;
use App\Domain\Camera\Repositories\CameraRepositoryInterface;
use App\Domain\Camera\ValueObjects\CameraId;
use App\Domain\Camera\ValueObjects\IpAddress;

final class CreateCameraUseCase
{
    public function __construct(
        private readonly CameraRepositoryInterface $cameras,
    ) {
    }

    public function execute(CreateCameraInput $input): Camera
    {
        $camera = Camera::register(
            id: CameraId::generate(),
            name: $input->name,
            location: $input->location,
            ipAddress: IpAddress::fromString($input->ipAddress),
        );

        $this->cameras->save($camera);

        return $camera;
    }
}
