<?php

declare(strict_types=1);

namespace App\Domain\ServiceOrder\Ports;

interface WebPushDispatcherInterface
{
    /**
     * @param  array{title: string, body: string, url: string, type?: string}  $payload
     */
    public function sendToUser(int $userId, array $payload): int;
}
