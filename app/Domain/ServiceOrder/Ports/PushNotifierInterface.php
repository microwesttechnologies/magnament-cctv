<?php

declare(strict_types=1);

namespace App\Domain\ServiceOrder\Ports;

interface PushNotifierInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyTechnician(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?int $serviceOrderId = null,
        ?string $url = null,
        array $data = [],
    ): void;
}
