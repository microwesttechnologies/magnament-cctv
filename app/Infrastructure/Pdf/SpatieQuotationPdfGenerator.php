<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Application\Quotation\QuotationSignatureSnapshot;
use App\Domain\Quotation\Entities\Quotation;
use App\Domain\Quotation\Ports\QuotationPdfGeneratorInterface;
use App\Infrastructure\Settings\EloquentCompanyIdentity;
use App\Models\Quotation as QuotationModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class SpatieQuotationPdfGenerator implements QuotationPdfGeneratorInterface
{
    public function __construct(
        private readonly EloquentCompanyIdentity $companyIdentity,
        private readonly QuotationSignatureSnapshot $signatureSnapshot,
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
            $companyName = trim((string) ($company['name'] ?? '')) !== '' ? (string) $company['name'] : 'CCTV Manager';
            $signatureBlock = [
                'signatureDataUri' => null,
                'signatoryName' => null,
                'companyName' => $companyName,
            ];

            $quotationId = $quotation->id()?->value();
            if ($quotationId !== null && Auth::check()) {
                $model = QuotationModel::query()->find($quotationId);
                if ($model !== null) {
                    $signatureBlock = $this->signatureSnapshot->resolveForPdf(
                        $model,
                        Auth::user(),
                        $companyName,
                    );
                }
            }

            if ($signatureBlock['signatureDataUri'] !== null && ! function_exists('imagecreatetruecolor')) {
                Log::warning('[SpatieQuotationPdfGenerator] PDF signature skipped; PHP GD is required to embed raster images', [
                    'quotation_id' => $quotation->id()?->value(),
                ]);
                $signatureBlock['signatureDataUri'] = null;
            }
            if ($logoDataUri !== null && ! function_exists('imagecreatetruecolor')) {
                Log::warning('[SpatieQuotationPdfGenerator] PDF logo skipped; PHP GD is required to embed raster images', [
                    'quotation_id' => $quotation->id()?->value(),
                ]);
                $logoDataUri = null;
            }

            try {
                $response = $this->buildDownload($quotation, $projectName, $company, $logoDataUri, $signatureBlock, $filename);
            } catch (Throwable $e) {
                if ($logoDataUri === null || ! str_contains($e->getMessage(), 'GD extension')) {
                    throw $e;
                }

                Log::warning('[SpatieQuotationPdfGenerator] PDF logo skipped after render error', [
                    'quotation_id' => $quotation->id()?->value(),
                    'error' => $e->getMessage(),
                ]);
                $response = $this->buildDownload($quotation, $projectName, $company, null, $signatureBlock, $filename);
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
     * @param  array{signatureDataUri: ?string, signatoryName: ?string, companyName: string}  $signatureBlock
     */
    private function buildDownload(
        Quotation $quotation,
        string $projectName,
        array $company,
        ?string $logoDataUri,
        array $signatureBlock,
        string $filename,
    ): Response {
        $builder = Pdf::view('pdf.quotations.show', [
            'quotation' => $quotation,
            'projectName' => $projectName,
            'lines' => $quotation->lines(),
            'company' => $company,
            'logoDataUri' => $logoDataUri,
            'signatureDataUri' => $signatureBlock['signatureDataUri'],
            'signatoryName' => $signatureBlock['signatoryName'],
            'signatoryCompany' => $signatureBlock['companyName'],
        ])
            ->format('a4')
            ->driver('dompdf')
            ->name($filename)
            ->download();

        return $builder->toResponse(request());
    }
}
