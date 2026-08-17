<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\ServiceOrder\Ports\WebPushDispatcherInterface;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

final class MinishlinkWebPushDispatcher implements WebPushDispatcherInterface
{
    public function sendToUser(int $userId, array $payload): int
    {
        $public = (string) config('webpush.vapid_public');
        $private = (string) config('webpush.vapid_private');
        if ($public === '' || $private === '') {
            Log::warning('[MinishlinkWebPushDispatcher.sendToUser] missing VAPID keys');

            return 0;
        }

        $subscriptions = PushSubscription::query()->where('user_id', $userId)->get();
        if ($subscriptions->isEmpty()) {
            Log::debug('[MinishlinkWebPushDispatcher.sendToUser] no subscriptions', [
                'user_id' => $userId,
            ]);

            return 0;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('webpush.vapid_subject', config('app.url')),
                    'publicKey' => $public,
                    'privateKey' => $private,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('[MinishlinkWebPushDispatcher.sendToUser] failed to init WebPush', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }

        $json = json_encode([
            'title' => $payload['title'],
            'body' => $payload['body'],
            'url' => $payload['url'] ?? '/tecnico?source=pwa',
            'type' => $payload['type'] ?? 'updated',
        ], JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            Log::error('[MinishlinkWebPushDispatcher.sendToUser] payload encode failed', [
                'user_id' => $userId,
            ]);

            return 0;
        }

        $sent = 0;
        foreach ($subscriptions as $row) {
            try {
                $report = $webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $row->endpoint,
                        'publicKey' => $row->public_key,
                        'authToken' => $row->auth_token,
                    ]),
                    $json,
                );
            } catch (Throwable $exception) {
                Log::error('[MinishlinkWebPushDispatcher.sendToUser] send failed', [
                    'user_id' => $userId,
                    'subscription_id' => $row->id,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($report->isSuccess()) {
                $sent++;

                continue;
            }

            $response = $report->getResponse();
            $status = $response?->getStatusCode();
            Log::warning('[MinishlinkWebPushDispatcher.sendToUser] rejected', [
                'user_id' => $userId,
                'subscription_id' => $row->id,
                'status' => $status,
                'reason' => $report->getReason(),
            ]);

            if (in_array($status, [404, 410], true)) {
                $row->delete();
            }
        }

        Log::info('[MinishlinkWebPushDispatcher.sendToUser] dispatched', [
            'user_id' => $userId,
            'sent' => $sent,
            'subscriptions' => $subscriptions->count(),
        ]);

        return $sent;
    }
}
