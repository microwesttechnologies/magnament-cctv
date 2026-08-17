<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Repositories;

use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\ValueObjects\ProjectId;
use App\Domain\Quotation\ValueObjects\QuotationId;

interface QuotationRepositoryInterface
{
    public function save(Quotation $quotation): Quotation;

    public function findById(QuotationId $id): ?Quotation;

    /** @return list<Quotation> */
    public function findByProject(ProjectId $projectId): array;

    /** @return list<Quotation> */
    public function all(): array;

    public function nextCode(): string;
}
