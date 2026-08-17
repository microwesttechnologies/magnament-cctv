<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

final class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $setting = AppSetting::query()->updateOrCreate(
            ['key' => 'vat_rate_percent'],
            [
                'value' => '16.0000',
                'description' => 'Porcentaje de IVA vigente configurable (sin hardcode en cálculos)',
            ],
        );

        Log::info('[AppSettingsSeeder] VAT setting ensured', [
            'key' => $setting->key,
            'value' => $setting->value,
        ]);
    }
}
