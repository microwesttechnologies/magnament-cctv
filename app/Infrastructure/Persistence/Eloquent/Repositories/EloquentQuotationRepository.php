<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Enums\QuotationStatus;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\Money;
use App\Domain\Quotation\ValueObjects\ProjectId;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Domain\Quotation\ValueObjects\QuotationLineData;
use App\Domain\Quotation\ValueObjects\VatRate;
use App\Models\Quotation as QuotationModel;
use App\Models\QuotationLine;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EloquentQuotationRepository implements QuotationRepositoryInterface
{
    public function save(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation): Quotation {
            $attributes = [
                'project_id' => $quotation->projectId()->value(),
                'code' => $quotation->code(),
                'work_description' => $quotation->workDescription(),
                'status' => $quotation->status()->value,
                'vat_rate_percent' => $quotation->vatRate()->percent(),
                'subtotal' => $quotation->subtotal()->amount(),
                'vat_amount' => $quotation->vatAmount()->amount(),
                'total' => $quotation->total()->amount(),
                'created_by' => $quotation->createdBy(),
            ];

            if ($quotation->id() === null) {
                $model = QuotationModel::query()->create($attributes);
                $quotation->assignId(QuotationId::fromInt((int) $model->id));
            } else {
                $model = QuotationModel::query()->findOrFail($quotation->id()->value());
                $model->fill($attributes);
                $model->save();
            }

            QuotationLine::query()->where('quotation_id', $model->id)->delete();

            foreach ($quotation->lines() as $index => $line) {
                QuotationLine::query()->create([
                    'quotation_id' => $model->id,
                    'product_name' => $line->productName(),
                    'quantity' => $line->quantity(),
                    'brand' => $line->brand(),
                    'serial' => $line->serial(),
                    'unit_price' => $line->unitPrice()->amount(),
                    'line_subtotal' => $line->lineSubtotal()->amount(),
                    'sort_order' => $line->sortOrder() ?: $index,
                ]);
            }

            Log::info('[EloquentQuotationRepository] saved', [
                'quotation_id' => $model->id,
                'status' => $model->status,
            ]);

            return $this->toEntity($model->fresh('lines'));
        });
    }

    public function findById(QuotationId $id): ?Quotation
    {
        $model = QuotationModel::query()->with('lines')->find($id->value());

        return $model === null ? null : $this->toEntity($model);
    }

    public function findByProject(ProjectId $projectId): array
    {
        return QuotationModel::query()
            ->with('lines')
            ->where('project_id', $projectId->value())
            ->latest()
            ->get()
            ->map(fn (QuotationModel $model): Quotation => $this->toEntity($model))
            ->all();
    }

    public function all(): array
    {
        return QuotationModel::query()
            ->with(['lines', 'project'])
            ->latest()
            ->get()
            ->map(fn (QuotationModel $model): Quotation => $this->toEntity($model))
            ->all();
    }

    public function nextCode(): string
    {
        $next = (int) QuotationModel::query()->max('id') + 1;

        return 'COT-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function toEntity(QuotationModel $model): Quotation
    {
        $lines = $model->lines
            ->map(function (QuotationLine $line): QuotationLineData {
                return new QuotationLineData(
                    productName: $line->product_name,
                    quantity: (string) $line->quantity,
                    brand: $line->brand,
                    serial: $line->serial,
                    unitPrice: Money::fromString((string) $line->unit_price),
                    sortOrder: (int) $line->sort_order,
                );
            })
            ->values()
            ->all();

        $entity = new Quotation(
            id: QuotationId::fromInt((int) $model->id),
            projectId: ProjectId::fromInt((int) $model->project_id),
            code: $model->code,
            workDescription: (string) $model->work_description,
            status: QuotationStatus::from($model->status),
            vatRate: VatRate::fromString((string) $model->vat_rate_percent),
            subtotal: Money::fromString((string) $model->subtotal),
            vatAmount: Money::fromString((string) $model->vat_amount),
            total: Money::fromString((string) $model->total),
            lines: $lines,
            createdAt: new DateTimeImmutable((string) $model->created_at),
            createdBy: $model->created_by !== null ? (int) $model->created_by : null,
        );

        return $entity;
    }
}
