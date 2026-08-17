<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Camera\Repositories\CameraRepositoryInterface;
use App\Domain\Order\Repositories\InstallationOrderRepositoryInterface;
use App\Domain\Quotation\Ports\AuditLoggerInterface;
use App\Domain\Quotation\Ports\QuotationPdfGeneratorInterface;
use App\Domain\Quotation\Ports\TraceabilityRecorderInterface;
use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Infrastructure\Audit\EloquentAuditLogger;
use App\Infrastructure\Pdf\SpatieQuotationPdfGenerator;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCameraRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInstallationOrderRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentQuotationRepository;
use App\Infrastructure\Settings\EloquentVatSettings;
use App\Infrastructure\Traceability\EloquentTraceabilityRecorder;
use Illuminate\Support\ServiceProvider;

/**
 * Aquí se conecta el dominio con la infraestructura.
 * Cuando algo pide la interfaz, Laravel entrega la implementación Eloquent.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CameraRepositoryInterface::class => EloquentCameraRepository::class,
        QuotationRepositoryInterface::class => EloquentQuotationRepository::class,
        InstallationOrderRepositoryInterface::class => EloquentInstallationOrderRepository::class,
        VatSettingsInterface::class => EloquentVatSettings::class,
        AuditLoggerInterface::class => EloquentAuditLogger::class,
        TraceabilityRecorderInterface::class => EloquentTraceabilityRecorder::class,
        QuotationPdfGeneratorInterface::class => SpatieQuotationPdfGenerator::class,
        \App\Domain\ServiceOrder\Ports\PushNotifierInterface::class => \App\Infrastructure\Notifications\DatabasePushNotifier::class,
    ];
}
