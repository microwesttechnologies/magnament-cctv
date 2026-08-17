<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Ports;

interface VatSettingsInterface
{
    public function currentVatRatePercent(): string;

    public function updateVatRatePercent(string $percent): void;
}
