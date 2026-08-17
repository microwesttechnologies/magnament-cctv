<?php

declare(strict_types=1);

namespace App\Domain\Camera\Enums;

enum CameraStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Maintenance = 'maintenance';

    public function isOperational(): bool
    {
        return $this === self::Online;
    }
}
