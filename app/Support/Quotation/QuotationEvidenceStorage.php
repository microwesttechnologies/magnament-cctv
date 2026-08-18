<?php

declare(strict_types=1);

namespace App\Support\Quotation;

use App\Models\QuotationEvidence;
use App\Rules\ValidRasterImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class QuotationEvidenceStorage
{
    public const MAX_FILES = 5;

    /** @return list<string> */
    public static function validationRules(): array
    {
        return [
            'evidences' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'evidences.*' => ['file', new ValidRasterImage(noun: 'La evidencia')],
        ];
    }

    public function storeFromRequest(Request $request, int $quotationId, ?int $userId): void
    {
        /** @var list<UploadedFile> $files */
        $files = array_values(array_filter($request->file('evidences', []) ?? []));

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('quotation_evidences', 'public');
            $stored = Storage::disk('public')->path($path);
            $mime = is_string($stored) && is_file($stored)
                ? ((new \finfo(FILEINFO_MIME_TYPE))->file($stored) ?: $file->getMimeType())
                : $file->getMimeType();

            QuotationEvidence::query()->create([
                'quotation_id' => $quotationId,
                'uploaded_by' => $userId,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => is_string($mime) ? $mime : null,
                'sort_order' => $index,
            ]);
        }

        if ($files !== []) {
            Log::info('[QuotationEvidenceStorage] stored', [
                'quotation_id' => $quotationId,
                'count' => count($files),
            ]);
        }
    }
}
