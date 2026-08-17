<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

final class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Genera claves VAPID para notificaciones push de la PWA de técnicos';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $exception) {
            $this->error('No se pudieron generar las claves VAPID: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Añade estas variables a .env (no las commitees):');
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('VAPID_SUBJECT='.(string) config('app.url'));

        return self::SUCCESS;
    }
}
