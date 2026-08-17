<?php

declare(strict_types=1);

namespace App\Domain\Quotation\Ports;

use App\Domain\Quotation\Entities\Quotation;
use Symfony\Component\HttpFoundation\Response;

interface QuotationPdfGeneratorInterface
{
    public function download(Quotation $quotation, string $projectName): Response;
}
