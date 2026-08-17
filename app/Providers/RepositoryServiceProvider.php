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
use App\Domain\ServiceOrder\Ports\PushNotifierInterface;
use App\Domain\ServiceOrder\Ports\WebPushDispatcherInterface;
use App\Infrastructure\Audit\EloquentAuditLogger;
use App\Infrastructure\Notifications\DatabasePushNotifier;
use App\Infrastructure\Notifications\MinishlinkWebPushDispatcher;
use App\Infrastructure\Notifications\NullWebPushDispatcher;
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
        PushNotifierInterface::class => DatabasePushNotifier::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(WebPushDispatcherInterface::class, function (): WebPushDispatcherInterface {
            $public = (string) config('webpush.vapid_public');
            $private = (string) config('webpush.vapid_private');
            if ($public === '' || $private === '' || ! class_exists(\Minishlink\WebPush\WebPush::class)) {
                return $this->app->make(NullWebPushDispatcher::class);
            }

            return $this->app->make(MinishlinkWebPushDispatcher::class);
        });
    }
}
