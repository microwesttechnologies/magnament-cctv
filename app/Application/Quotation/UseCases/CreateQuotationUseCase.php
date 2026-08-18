<?php

declare(strict_types=1);

namespace App\Application\Quotation\UseCases;

use App\Application\Quotation\DTOs\CreateQuotationInput;
use App\Application\Quotation\DTOs\QuotationLineInput;
use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Ports\AuditLoggerInterface;
use App\Domain\Quotation\Ports\TraceabilityRecorderInterface;
use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\Money;
use App\Domain\Quotation\ValueObjects\ProjectId;
use App\Domain\Quotation\ValueObjects\QuotationLineData;
use App\Domain\Quotation\ValueObjects\VatRate;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateQuotationUseCase
{
    public function __construct(
        private readonly QuotationRepositoryInterface $quotations,
        private readonly VatSettingsInterface $vatSettings,
        private readonly AuditLoggerInterface $audit,
        private readonly TraceabilityRecorderInterface $traceability,
    ) {
    }

    public function execute(CreateQuotationInput $input): Quotation
    {
        Log::info('[Quotation.CreateQuotationUseCase] START', [
            'project_id' => $input->projectId,
            'lines' => count($input->lines),
        ]);

        try {
            $vatPercent = $input->vatRatePercent ?? $this->vatSettings->currentVatRatePercent();
            $vatRate = VatRate::fromString($vatPercent);
            $lines = array_map(
                fn (QuotationLineInput $line): QuotationLineData => new QuotationLineData(
                    productName: $line->productName,
                    quantity: $line->quantity,
                    brand: $line->brand,
                    serial: $line->serial,
                    unitPrice: Money::fromString($line->unitPrice),
                    sortOrder: $line->sortOrder,
                ),
                $input->lines,
            );

            $quotation = Quotation::draft(
                projectId: ProjectId::fromInt($input->projectId),
                code: $this->quotations->nextCode(),
                workDescription: $input->workDescription,
                designedSolution: $input->designedSolution,
                vatRate: $vatRate,
                lines: $lines,
                createdBy: $input->createdBy,
            );

            $saved = $this->quotations->save($quotation);

            $this->audit->record(
                auditableType: Quotation::class,
                auditableId: $saved->id()->value(),
                action: 'quotation.created',
                newValues: [
                    'code' => $saved->code(),
                    'status' => $saved->status()->value,
                    'vat_rate_percent' => $saved->vatRate()->percent(),
                    'total' => $saved->total()->amount(),
                ],
                userId: $input->createdBy,
            );

            $this->traceability->record(
                projectId: $input->projectId,
                eventType: 'quotation.created',
                title: 'Cotización creada: '.$saved->code(),
                payload: [
                    'quotation_id' => $saved->id()->value(),
                    'code' => $saved->code(),
                    'total' => $saved->total()->amount(),
                ],
                quotationId: $saved->id()->value(),
                userId: $input->createdBy,
            );

            Log::info('[Quotation.CreateQuotationUseCase] SUCCESS', [
                'quotation_id' => $saved->id()->value(),
                'project_id' => $input->projectId,
                'status' => $saved->status()->value,
            ]);

            return $saved;
        } catch (Throwable $e) {
            Log::error('[Quotation.CreateQuotationUseCase] ERROR', [
                'project_id' => $input->projectId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
