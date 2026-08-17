<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Entities;

use App\Domain\Quotation\Enums\QuotationStatus;
use App\Domain\Quotation\Exceptions\InvalidQuotationTransition;
use App\Domain\Quotation\Exceptions\QuotationNotConvertible;
use App\Domain\Quotation\ValueObjects\Money;
use App\Domain\Quotation\ValueObjects\ProjectId;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Domain\Quotation\ValueObjects\QuotationLineData;
use App\Domain\Quotation\ValueObjects\VatRate;
use DateTimeImmutable;
use InvalidArgumentException;

final class Quotation
{
    /** @param list<QuotationLineData> $lines */
    public function __construct(
        private ?QuotationId $id,
        private readonly ProjectId $projectId,
        private string $code,
        private string $workDescription,
        private string $designedSolution,
        private QuotationStatus $status,
        private VatRate $vatRate,
        private Money $subtotal,
        private Money $vatAmount,
        private Money $total,
        private array $lines,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?int $createdBy,
    ) {
    }

    /** @param list<QuotationLineData> $lines */
    public static function draft(
        ProjectId $projectId,
        string $code,
        string $workDescription,
        string $designedSolution,
        VatRate $vatRate,
        array $lines,
        ?int $createdBy,
    ): self {
        $quote = new self(
            id: null,
            projectId: $projectId,
            code: $code,
            workDescription: $workDescription,
            designedSolution: $designedSolution,
            status: QuotationStatus::Borrador,
            vatRate: $vatRate,
            subtotal: Money::zero(),
            vatAmount: Money::zero(),
            total: Money::zero(),
            lines: [],
            createdAt: new DateTimeImmutable(),
            createdBy: $createdBy,
        );

        $quote->replaceLines($lines);
        $quote->recalculateTotals($vatRate);

        return $quote;
    }

    public function id(): ?QuotationId
    {
        return $this->id;
    }

    public function assignId(QuotationId $id): void
    {
        $this->id = $id;
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function workDescription(): string
    {
        return $this->workDescription;
    }

    public function designedSolution(): string
    {
        return $this->designedSolution;
    }

    public function status(): QuotationStatus
    {
        return $this->status;
    }

    public function vatRate(): VatRate
    {
        return $this->vatRate;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function vatAmount(): Money
    {
        return $this->vatAmount;
    }

    public function total(): Money
    {
        return $this->total;
    }

    /** @return list<QuotationLineData> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function createdBy(): ?int
    {
        return $this->createdBy;
    }

    public function updateWorkDescription(string $description): void
    {
        $this->assertEditable();
        $this->workDescription = $description;
    }

    public function updateDesignedSolution(string $solution): void
    {
        $this->assertEditable();
        $this->designedSolution = $solution;
    }

    /** @param list<QuotationLineData> $lines */
    public function replaceLines(array $lines): void
    {
        $this->assertEditable();

        if ($lines === []) {
            throw new InvalidArgumentException('La cotización requiere al menos una línea.');
        }

        $this->lines = array_values($lines);
    }

    public function recalculateTotals(VatRate $vatRate): void
    {
        if ($this->status->freezesVatRate() && $vatRate->percent() !== $this->vatRate->percent()) {
            // Estados emitidos/aprobados conservan el snapshot histórico.
            $vatRate = $this->vatRate;
        } elseif ($this->status->isEditable()) {
            $this->vatRate = $vatRate;
        }

        $subtotal = Money::zero();
        foreach ($this->lines as $line) {
            $subtotal = $subtotal->add($line->lineSubtotal());
        }

        $this->subtotal = $subtotal;
        $this->vatAmount = $subtotal->percentageOf($this->vatRate->percent());
        $this->total = $this->subtotal->add($this->vatAmount);
    }

    public function transitionTo(QuotationStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidQuotationTransition::fromTo($this->status->value, $target->value);
        }

        $this->status = $target;
    }

    public function assertConvertibleToOrder(): void
    {
        if (! $this->status->canConvertToOrder()) {
            throw QuotationNotConvertible::because(
                'Solo una cotización aprobada puede convertirse en Orden de Instalación/Implementación.'
            );
        }
    }

    public function markConverted(): void
    {
        $this->assertConvertibleToOrder();
        $this->transitionTo(QuotationStatus::Convertida);
    }

    private function assertEditable(): void
    {
        if (! $this->status->isEditable()) {
            throw new InvalidArgumentException('Solo se pueden editar cotizaciones en borrador.');
        }
    }
}
