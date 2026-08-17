<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\ServiceOrder\Ports\PushNotifierInterface;
use App\Domain\ServiceOrder\Ports\WebPushDispatcherInterface;
use App\Models\TechnicianNotification;
use Illuminate\Support\Facades\Log;

final class DatabasePushNotifier implements PushNotifierInterface
{
    public function __construct(
        private readonly WebPushDispatcherInterface $dispatcher,
    ) {
    }

    public function notifyTechnician(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?int $serviceOrderId = null,
        ?string $url = null,
        array $data = [],
    ): void {
        TechnicianNotification::query()->create([
            'user_id' => $userId,
            'service_order_id' => $serviceOrderId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        $sent = $this->dispatcher->sendToUser($userId, [
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/tecnico?source=pwa',
            'type' => $type,
        ]);

        Log::info('[DatabasePushNotifier] technician notified', [
            'user_id' => $userId,
            'type' => $type,
            'service_order_id' => $serviceOrderId,
            'web_push_sent' => $sent,
            'payload' => $data,
        ]);
    }
}
