<?php

declare(strict_types=1);

namespace App\Application\Quotation;

use App\Infrastructure\Settings\EloquentUserSignature;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class QuotationSignatureSnapshot
{
    public function __construct(
        private readonly EloquentUserSignature $userSignature,
    ) {
    }

    /**
     * @return array{
     *     signatureDataUri: ?string,
     *     signatoryName: ?string,
     *     companyName: string
     * }
     */
    public function resolveForPdf(Quotation $quotation, User $signatory, string $companyName): array
    {
        if ($quotation->signature_snapshot_path === null) {
            $this->freeze($quotation, $signatory);
            $quotation->refresh();
        }

        return [
            'signatureDataUri' => $this->userSignature->dataUriFromPath($quotation->signature_snapshot_path),
            'signatoryName' => $quotation->signatory_name ?? $signatory->name,
            'companyName' => $companyName,
        ];
    }

    private function freeze(Quotation $quotation, User $signatory): void
    {
        $snapshotPath = null;

        if (is_string($signatory->signature_path) && $signatory->signature_path !== '') {
            $source = $signatory->signature_path;
            if (Storage::disk('public')->exists($source)) {
                $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'png';
                $snapshotPath = 'quotation_signatures/'.$quotation->id.'/'.now()->format('YmdHis').'.'.$extension;
                Storage::disk('public')->makeDirectory('quotation_signatures/'.$quotation->id);
                Storage::disk('public')->copy($source, $snapshotPath);
            }
        }

        $quotation->signatory_user_id = $signatory->id;
        $quotation->signatory_name = $signatory->name;
        $quotation->signature_snapshot_path = $snapshotPath;
        $quotation->save();
    }
}
