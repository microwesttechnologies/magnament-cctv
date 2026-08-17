<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\ServiceOrder\Ports\WebPushDispatcherInterface;

final class ArrayWebPushDispatcher implements WebPushDispatcherInterface
{
    /** @var list<array{user_id: int, payload: array<string, mixed>}> */
    public array $sent = [];

    public function sendToUser(int $userId, array $payload): int
    {
        $this->sent[] = [
            'user_id' => $userId,
            'payload' => $payload,
        ];

        return 1;
    }
}
