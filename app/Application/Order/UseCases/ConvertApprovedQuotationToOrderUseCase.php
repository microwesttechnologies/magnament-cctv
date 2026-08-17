<?php

declare(strict_types=1);

namespace App\Application\Order\UseCases;

use App\Domain\Order\Repositories\InstallationOrderRepositoryInterface;
use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Exceptions\QuotationNotConvertible;
use App\Domain\Quotation\Exceptions\QuotationNotFoundException;
use App\Domain\Quotation\Ports\AuditLoggerInterface;
use App\Domain\Quotation\Ports\TraceabilityRecorderInterface;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\QuotationId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ConvertApprovedQuotationToOrderUseCase
{
    public function __construct(
        private readonly QuotationRepositoryInterface $quotations,
        private readonly InstallationOrderRepositoryInterface $orders,
        private readonly AuditLoggerInterface $audit,
        private readonly TraceabilityRecorderInterface $traceability,
    ) {
    }

    /**
     * @return array{quotation: Quotation, order_id: int, order_code: string}
     */
    public function execute(int $quotationId, ?int $userId, ?string $notes = null): array
    {
        Log::info('[Order.ConvertApprovedQuotationToOrderUseCase] START', [
            'quotation_id' => $quotationId,
        ]);

        try {
            return DB::transaction(function () use ($quotationId, $userId, $notes): array {
                $quotation = $this->quotations->findById(QuotationId::fromInt($quotationId));
                if ($quotation === null) {
                    throw QuotationNotFoundException::withId($quotationId);
                }

                $quotation->assertConvertibleToOrder();

                if ($this->orders->existsForQuotation($quotation->id())) {
                    Log::warning('[Order.ConvertApprovedQuotationToOrderUseCase] ALREADY_EXISTS', [
                        'quotation_id' => $quotationId,
                    ]);
                    throw QuotationNotConvertible::because('Esta cotización ya tiene una Orden asociada.');
                }

                $order = $this->orders->create(
                    projectId: $quotation->projectId()->value(),
                    quotationId: $quotation->id()->value(),
                    code: $this->orders->nextCode(),
                    status: 'pendiente',
                    notes: $notes,
                );

                $quotation->markConverted();
                $saved = $this->quotations->save($quotation);

                $this->audit->record(
                    auditableType: Quotation::class,
                    auditableId: $saved->id()->value(),
                    action: 'quotation.converted_to_order',
                    newValues: [
                        'order_id' => $order['id'],
                        'order_code' => $order['code'],
                        'status' => $saved->status()->value,
                    ],
                    userId: $userId,
                );

                $this->traceability->record(
                    projectId: $saved->projectId()->value(),
                    eventType: 'quotation.converted',
                    title: 'Cotización convertida a Orden: '.$order['code'],
                    payload: [
                        'quotation_id' => $saved->id()->value(),
                        'order_id' => $order['id'],
                        'order_code' => $order['code'],
                    ],
                    quotationId: $saved->id()->value(),
                    orderId: $order['id'],
                    userId: $userId,
                );

                $this->traceability->record(
                    projectId: $saved->projectId()->value(),
                    eventType: 'order.created',
                    title: 'Orden de Instalación/Implementación creada: '.$order['code'],
                    payload: [
                        'order_id' => $order['id'],
                        'quotation_id' => $saved->id()->value(),
                    ],
                    quotationId: $saved->id()->value(),
                    orderId: $order['id'],
                    userId: $userId,
                );

                Log::info('[Order.ConvertApprovedQuotationToOrderUseCase] SUCCESS', [
                    'quotation_id' => $quotationId,
                    'order_id' => $order['id'],
                    'order_code' => $order['code'],
                ]);

                return [
                    'quotation' => $saved,
                    'order_id' => $order['id'],
                    'order_code' => $order['code'],
                ];
            });
        } catch (QuotationNotConvertible $e) {
            Log::warning('[Order.ConvertApprovedQuotationToOrderUseCase] BLOCKED', [
                'quotation_id' => $quotationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('[Order.ConvertApprovedQuotationToOrderUseCase] ERROR', [
                'quotation_id' => $quotationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
