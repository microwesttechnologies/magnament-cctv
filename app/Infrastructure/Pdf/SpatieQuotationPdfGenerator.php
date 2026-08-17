<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Ports\QuotationPdfGeneratorInterface;
use App\Infrastructure\Settings\EloquentCompanyIdentity;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class SpatieQuotationPdfGenerator implements QuotationPdfGeneratorInterface
{
    public function __construct(
        private readonly EloquentCompanyIdentity $companyIdentity,
    ) {
    }

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
            $company = $this->companyIdentity->snapshot();
            $logoDataUri = $this->companyIdentity->logoDataUri();
            if ($logoDataUri !== null && ! function_exists('imagecreatetruecolor')) {
                Log::warning('[SpatieQuotationPdfGenerator] PDF logo skipped; PHP GD is required to embed raster images', [
                    'quotation_id' => $quotation->id()?->value(),
                ]);
                $logoDataUri = null;
            }

            try {
                $response = $this->buildDownload($quotation, $projectName, $company, $logoDataUri, $filename);
            } catch (Throwable $e) {
                if ($logoDataUri === null || ! str_contains($e->getMessage(), 'GD extension')) {
                    throw $e;
                }

                Log::warning('[SpatieQuotationPdfGenerator] PDF logo skipped after render error', [
                    'quotation_id' => $quotation->id()?->value(),
                    'error' => $e->getMessage(),
                ]);
                $response = $this->buildDownload($quotation, $projectName, $company, null, $filename);
            }

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

    /**
     * @param  array{logo_path: ?string, logo_url: ?string, name: string, nit: string, phone: string, email: string}  $company
     */
    private function buildDownload(
        Quotation $quotation,
        string $projectName,
        array $company,
        ?string $logoDataUri,
        string $filename,
    ): Response {
        $builder = Pdf::view('pdf.quotations.show', [
            'quotation' => $quotation,
            'projectName' => $projectName,
            'lines' => $quotation->lines(),
            'company' => $company,
            'logoDataUri' => $logoDataUri,
        ])
            ->format('a4')
            ->driver('dompdf')
            ->name($filename)
            ->download();

        return $builder->toResponse(request());
    }
}
