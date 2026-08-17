<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent: detalle de infraestructura (persistencia).
 * Es distinto de la entidad de dominio Camera; el repositorio traduce entre ambos.
 */
final class CameraModel extends Model
{
    protected $table = 'cameras';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'location',
        'ip_address',
        'status',
        'created_at',
    ];
}
