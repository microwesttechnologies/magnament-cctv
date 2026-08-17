<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Ports\QuotationPdfGeneratorInterface;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class SpatieQuotationPdfGenerator implements QuotationPdfGeneratorInterface
{
    public function download(Quotation $quotation, string $projectName): Response
    {
        Log::info('[FIX] Generating quotation PDF download', [
            'quotation_id' => $quotation->id()?->value(),
            'code' => $quotation->code(),
            'project_name' => $projectName,
        ]);
        Log::info('[SpatieQuotationPdfGenerator] START', [
            'quotation_id' => $quotation->id()?->value(),
            'code' => $quotation->code(),
        ]);

        try {
            $filename = 'cotizacion-'.$quotation->code().'.pdf';

            // Spatie 2.12.1: download() returns PdfBuilder (Responsable), not a Response.
            $builder = Pdf::view('pdf.quotations.show', [
                'quotation' => $quotation,
                'projectName' => $projectName,
                'lines' => $quotation->lines(),
            ])
                ->format('a4')
                ->driver('dompdf')
                ->name($filename)
                ->download();

            $response = $builder->toResponse(request());

            Log::info('[FIX] PDF download response built', [
                'quotation_id' => $quotation->id()?->value(),
                'filename' => $filename,
                'status' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type'),
                'content_disposition' => $response->headers->get('Content-Disposition'),
            ]);

            Log::info('[SpatieQuotationPdfGenerator] SUCCESS', [
                'quotation_id' => $quotation->id()?->value(),
            ]);

            return $response;
        } catch (Throwable $e) {
            Log::error('[FIX] PDF download failed', [
                'quotation_id' => $quotation->id()?->value(),
                'error' => $e->getMessage(),
            ]);
            Log::error('[SpatieQuotationPdfGenerator] ERROR', [
                'quotation_id' => $quotation->id()?->value(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
