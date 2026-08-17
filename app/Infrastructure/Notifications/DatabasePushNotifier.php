<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\ServiceOrder\Ports\PushNotifierInterface;
use App\Models\PushSubscription;
use App\Models\TechnicianNotification;
use Illuminate\Support\Facades\Log;

final class DatabasePushNotifier implements PushNotifierInterface
{
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

        $count = PushSubscription::query()->where('user_id', $userId)->count();
        Log::info('[DatabasePushNotifier] technician notified', [
            'user_id' => $userId,
            'type' => $type,
            'service_order_id' => $serviceOrderId,
            'push_subscriptions' => $count,
            'payload' => $data,
        ]);
    }
}
