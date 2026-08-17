<?php

declare(strict_types=1);

namespace App\Application\Quotation\UseCases;

use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Enums\QuotationStatus;
use App\Domain\Quotation\Exceptions\InvalidQuotationTransition;
use App\Domain\Quotation\Exceptions\QuotationNotFoundException;
use App\Domain\Quotation\Ports\AuditLoggerInterface;
use App\Domain\Quotation\Ports\TraceabilityRecorderInterface;
use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Domain\Quotation\ValueObjects\VatRate;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TransitionQuotationStatusUseCase
{
    public function __construct(
        private readonly QuotationRepositoryInterface $quotations,
        private readonly VatSettingsInterface $vatSettings,
        private readonly AuditLoggerInterface $audit,
        private readonly TraceabilityRecorderInterface $traceability,
    ) {
    }

    public function execute(int $quotationId, QuotationStatus $target, ?int $userId): Quotation
    {
        Log::info('[Quotation.TransitionQuotationStatusUseCase] START', [
            'quotation_id' => $quotationId,
            'target' => $target->value,
        ]);

        try {
            $quotation = $this->quotations->findById(QuotationId::fromInt($quotationId));
            if ($quotation === null) {
                throw QuotationNotFoundException::withId($quotationId);
            }

            $from = $quotation->status()->value;

            if ($quotation->status() === QuotationStatus::Borrador && $target === QuotationStatus::Emitida) {
                // Congela el IVA vigente al emitir.
                $quotation->recalculateTotals(VatRate::fromString($this->vatSettings->currentVatRatePercent()));
            }

            $quotation->transitionTo($target);
            $saved = $this->quotations->save($quotation);

            $this->audit->record(
                auditableType: Quotation::class,
                auditableId: $saved->id()->value(),
                action: 'quotation.status_changed',
                oldValues: ['status' => $from],
                newValues: [
                    'status' => $saved->status()->value,
                    'vat_rate_percent' => $saved->vatRate()->percent(),
                ],
                userId: $userId,
            );

            if ($target === QuotationStatus::Aprobada) {
                $this->traceability->record(
                    projectId: $saved->projectId()->value(),
                    eventType: 'quotation.approved',
                    title: 'Cotización aprobada: '.$saved->code(),
                    payload: ['quotation_id' => $saved->id()->value()],
                    quotationId: $saved->id()->value(),
                    userId: $userId,
                );
            }

            Log::info('[Quotation.TransitionQuotationStatusUseCase] SUCCESS', [
                'quotation_id' => $quotationId,
                'from' => $from,
                'status' => $saved->status()->value,
            ]);

            return $saved;
        } catch (InvalidQuotationTransition $e) {
            Log::warning('[Quotation.TransitionQuotationStatusUseCase] INVALID_TRANSITION', [
                'quotation_id' => $quotationId,
                'target' => $target->value,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('[Quotation.TransitionQuotationStatusUseCase] ERROR', [
                'quotation_id' => $quotationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
