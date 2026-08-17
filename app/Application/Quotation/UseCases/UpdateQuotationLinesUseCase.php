<?php

declare(strict_types=1);

namespace App\Application\Quotation\UseCases;

use App\Application\Quotation\DTOs\QuotationLineInput;
use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Exceptions\QuotationNotFoundException;
use App\Domain\Quotation\Ports\AuditLoggerInterface;
use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\Money;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Domain\Quotation\ValueObjects\QuotationLineData;
use App\Domain\Quotation\ValueObjects\VatRate;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateQuotationLinesUseCase
{
    public function __construct(
        private readonly QuotationRepositoryInterface $quotations,
        private readonly VatSettingsInterface $vatSettings,
        private readonly AuditLoggerInterface $audit,
    ) {
    }

    /**
     * @param  list<QuotationLineInput>  $lines
     */
    public function execute(
        int $quotationId,
        string $workDescription,
        string $designedSolution,
        array $lines,
        ?int $userId,
    ): Quotation {
        Log::info('[Quotation.UpdateQuotationLinesUseCase] START', [
            'quotation_id' => $quotationId,
            'lines' => count($lines),
        ]);

        try {
            $quotation = $this->quotations->findById(QuotationId::fromInt($quotationId));
            if ($quotation === null) {
                throw QuotationNotFoundException::withId($quotationId);
            }

            $old = [
                'work_description' => $quotation->workDescription(),
                'designed_solution' => $quotation->designedSolution(),
                'total' => $quotation->total()->amount(),
                'vat_rate_percent' => $quotation->vatRate()->percent(),
            ];

            $lineData = array_map(
                fn (QuotationLineInput $line): QuotationLineData => new QuotationLineData(
                    productName: $line->productName,
                    quantity: $line->quantity,
                    brand: $line->brand,
                    serial: $line->serial,
                    unitPrice: Money::fromString($line->unitPrice),
                    sortOrder: $line->sortOrder,
                ),
                $lines,
            );

            $quotation->updateWorkDescription($workDescription);
            $quotation->updateDesignedSolution($designedSolution);
            $quotation->replaceLines($lineData);
            $quotation->recalculateTotals(VatRate::fromString($this->vatSettings->currentVatRatePercent()));

            $saved = $this->quotations->save($quotation);

            $this->audit->record(
                auditableType: Quotation::class,
                auditableId: $saved->id()->value(),
                action: 'quotation.lines_updated',
                oldValues: $old,
                newValues: [
                    'work_description' => $saved->workDescription(),
                    'designed_solution' => $saved->designedSolution(),
                    'total' => $saved->total()->amount(),
                    'vat_rate_percent' => $saved->vatRate()->percent(),
                    'lines_count' => count($saved->lines()),
                ],
                userId: $userId,
            );

            Log::info('[Quotation.UpdateQuotationLinesUseCase] SUCCESS', [
                'quotation_id' => $quotationId,
                'status' => $saved->status()->value,
            ]);

            return $saved;
        } catch (Throwable $e) {
            Log::error('[Quotation.UpdateQuotationLinesUseCase] ERROR', [
                'quotation_id' => $quotationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
