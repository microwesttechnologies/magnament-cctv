<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\ServiceOrder\Ports\WebPushDispatcherInterface;
use Illuminate\Support\Facades\Log;

final class NullWebPushDispatcher implements WebPushDispatcherInterface
{
    public function sendToUser(int $userId, array $payload): int
    {
        Log::debug('[NullWebPushDispatcher.sendToUser] skipped, no VAPID keys', [
            'user_id' => $userId,
            'title' => $payload['title'] ?? null,
        ]);

        return 0;
    }
}
