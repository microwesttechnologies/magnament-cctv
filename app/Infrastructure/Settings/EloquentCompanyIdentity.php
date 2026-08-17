<?php

declare(strict_types=1);

namespace App\Infrastructure\Settings;

use App\Models\AppSetting;
use App\Support\Cache\CacheInvalidator;
use App\Support\Cache\CacheKeys;
use App\Support\Cache\CacheTtl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class EloquentCompanyIdentity
{
    public const LOGO_KEY = 'company_logo_path';

    public const NAME_KEY = 'company_name';

    public const NIT_KEY = 'company_nit';

    public const PHONE_KEY = 'company_phone';

    public const EMAIL_KEY = 'company_email';

    /**
     * @return array{
     *     logo_path: ?string,
     *     logo_url: ?string,
     *     name: string,
     *     nit: string,
     *     phone: string,
     *     email: string
     * }
     */
    public function snapshot(): array
    {
        return Cache::remember(CacheKeys::SETTINGS_COMPANY, CacheTtl::SETTINGS, function (): array {
            $values = AppSetting::query()
                ->whereIn('key', [self::LOGO_KEY, self::NAME_KEY, self::NIT_KEY, self::PHONE_KEY, self::EMAIL_KEY])
                ->pluck('value', 'key');

            $path = $values[self::LOGO_KEY] ?? null;
            $path = is_string($path) && $path !== '' ? $path : null;

            return [
                'logo_path' => $path,
                'logo_url' => $path && Storage::disk('public')->exists($path)
                    ? asset('storage/'.$path)
                    : null,
                'name' => (string) ($values[self::NAME_KEY] ?? ''),
                'nit' => (string) ($values[self::NIT_KEY] ?? ''),
                'phone' => (string) ($values[self::PHONE_KEY] ?? ''),
                'email' => (string) ($values[self::EMAIL_KEY] ?? ''),
            ];
        });
    }

    public function logoDataUri(): ?string
    {
        $path = $this->snapshot()['logo_path'];
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
        $binary = Storage::disk('public')->get($path);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * @param  array{name?: string, nit?: string, phone?: string, email?: string}  $fields
     */
    public function update(array $fields, ?UploadedFile $logo = null, bool $removeLogo = false): void
    {
        $this->upsert(self::NAME_KEY, $fields['name'] ?? '', 'Nombre comercial de la empresa');
        $this->upsert(self::NIT_KEY, $fields['nit'] ?? '', 'NIT de la empresa');
        $this->upsert(self::PHONE_KEY, $fields['phone'] ?? '', 'Teléfono de la empresa');
        $this->upsert(self::EMAIL_KEY, $fields['email'] ?? '', 'Correo de la empresa');

        if ($removeLogo) {
            $this->deleteLogoFile();
            $this->upsert(self::LOGO_KEY, '', 'Ruta del logo de empresa en storage público');
        } elseif ($logo instanceof UploadedFile) {
            $this->deleteLogoFile();
            $path = $logo->store('company', 'public');
            $this->upsert(self::LOGO_KEY, $path, 'Ruta del logo de empresa en storage público');
        }

        CacheInvalidator::settings();
    }

    private function deleteLogoFile(): void
    {
        $current = AppSetting::query()->where('key', self::LOGO_KEY)->value('value');
        if (is_string($current) && $current !== '' && Storage::disk('public')->exists($current)) {
            Storage::disk('public')->delete($current);
        }
    }

    private function upsert(string $key, string $value, string $description): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ],
        );
    }
}
