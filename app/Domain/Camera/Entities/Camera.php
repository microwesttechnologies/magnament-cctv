<?php

declare(strict_types=1);

namespace App\Domain\Camera\Entities;

use App\Domain\Camera\Enums\CameraStatus;
use App\Domain\Camera\ValueObjects\CameraId;
use App\Domain\Camera\ValueObjects\IpAddress;
use DateTimeImmutable;

/**
 * Entidad de dominio. No conoce Eloquent, base de datos ni HTTP.
 * Contiene únicamente las reglas de negocio de una cámara.
 */
final class Camera
{
    public function __construct(
        private readonly CameraId $id,
        private string $name,
        private string $location,
        private IpAddress $ipAddress,
        private CameraStatus $status,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function register(
        CameraId $id,
        string $name,
        string $location,
        IpAddress $ipAddress,
    ): self {
        return new self(
            id: $id,
            name: $name,
            location: $location,
            ipAddress: $ipAddress,
            status: CameraStatus::Offline,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function markOnline(): void
    {
        $this->status = CameraStatus::Online;
    }

    public function markOffline(): void
    {
        $this->status = CameraStatus::Offline;
    }

    public function sendToMaintenance(): void
    {
        $this->status = CameraStatus::Maintenance;
    }

    public function id(): CameraId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function location(): string
    {
        return $this->location;
    }

    public function ipAddress(): IpAddress
    {
        return $this->ipAddress;
    }

    public function status(): CameraStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
