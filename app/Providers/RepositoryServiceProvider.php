<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Camera\Repositories\CameraRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCameraRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Aquí se conecta el dominio con la infraestructura.
 * Cuando algo pide la interfaz, Laravel entrega la implementación Eloquent.
 * Para cambiar de motor (p. ej. una API externa) solo se cambia esta línea.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CameraRepositoryInterface::class => EloquentCameraRepository::class,
    ];
}
