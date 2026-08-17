<?php

declare(strict_types=1);

namespace App\Infrastructure\Settings;

use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class EloquentVatSettings implements VatSettingsInterface
{
    public const KEY = 'vat_rate_percent';

    public function currentVatRatePercent(): string
    {
        $value = AppSetting::query()->where('key', self::KEY)->value('value');

        if ($value === null || ! is_numeric($value)) {
            Log::warning('[EloquentVatSettings] missing VAT setting; refusing hardcoded fallback');
            throw new InvalidArgumentException(
                'No hay IVA configurado. Defina vat_rate_percent en configuración de la aplicación.'
            );
        }

        return bcadd((string) $value, '0', 4);
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

        Log::info('[EloquentVatSettings] VAT updated', ['value' => $percent]);
    }
}
