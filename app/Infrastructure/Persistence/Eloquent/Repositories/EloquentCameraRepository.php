<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Camera\Entities\Camera;
use App\Domain\Camera\Enums\CameraStatus;
use App\Domain\Camera\Repositories\CameraRepositoryInterface;
use App\Domain\Camera\ValueObjects\CameraId;
use App\Domain\Camera\ValueObjects\IpAddress;
use App\Infrastructure\Persistence\Eloquent\Models\CameraModel;
use DateTimeImmutable;

/**
 * Implementación concreta del contrato del dominio usando Eloquent.
 * Traduce entre el modelo de persistencia y la entidad de dominio.
 */
final class EloquentCameraRepository implements CameraRepositoryInterface
{
    public function save(Camera $camera): void
    {
        CameraModel::query()->updateOrCreate(
            ['id' => $camera->id()->value()],
            [
                'name' => $camera->name(),
                'location' => $camera->location(),
                'ip_address' => $camera->ipAddress()->value(),
                'status' => $camera->status()->value,
                'created_at' => $camera->createdAt()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function findById(CameraId $id): ?Camera
    {
        $model = CameraModel::query()->find($id->value());

        return $model === null ? null : $this->toEntity($model);
    }

    /**
     * @return Camera[]
     */
    public function all(): array
    {
        return CameraModel::query()
            ->orderBy('created_at')
            ->get()
            ->map(fn (CameraModel $model): Camera => $this->toEntity($model))
            ->all();
    }

    public function delete(CameraId $id): void
    {
        CameraModel::query()->where('id', $id->value())->delete();
    }

    private function toEntity(CameraModel $model): Camera
    {
        return new Camera(
            id: CameraId::fromString($model->id),
            name: $model->name,
            location: $model->location,
            ipAddress: IpAddress::fromString($model->ip_address),
            status: CameraStatus::from($model->status),
            createdAt: new DateTimeImmutable((string) $model->created_at),
        );
    }
}
