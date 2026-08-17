<?php

declare(strict_types=1);

namespace App\Infrastructure\Settings;

use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Models\AppSetting;
use App\Support\Cache\CacheInvalidator;
use App\Support\Cache\CacheKeys;
use App\Support\Cache\CacheTtl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class EloquentVatSettings implements VatSettingsInterface
{
    public const KEY = 'vat_rate_percent';

    public function currentVatRatePercent(): string
    {
        $cached = Cache::remember(CacheKeys::SETTINGS_VAT, CacheTtl::SETTINGS, function (): ?string {
            $value = AppSetting::query()->where('key', self::KEY)->value('value');

            if ($value === null || ! is_numeric($value)) {
                return null;
            }

            return bcadd((string) $value, '0', 4);
        });

        if ($cached === null) {
            Log::warning('[EloquentVatSettings] missing VAT setting; refusing hardcoded fallback');
            throw new InvalidArgumentException(
                'No hay IVA configurado. Defina vat_rate_percent en configuración de la aplicación.'
            );
        }

        return $cached;
    }

    public function updateVatRatePercent(string $percent): void
    {
        if (! is_numeric($percent) || bccomp($percent, '0', 4) < 0) {
            throw new InvalidArgumentException('El porcentaje de IVA debe ser un número >= 0.');
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            [
                'value' => bcadd($percent, '0', 4),
                'description' => 'Porcentaje de IVA vigente configurable',
            ],
        );

        CacheInvalidator::settings();
        Log::info('[EloquentVatSettings] VAT updated', ['value' => $percent]);
    }
}
