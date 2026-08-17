<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

final class ValidRasterImage implements ValidationRule
{
    /**
     * @param  list<string>  $allowedMimes
     */
    public function __construct(
        private readonly array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'],
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! $value instanceof UploadedFile) {
            $fail('El logo debe ser un archivo de imagen JPEG, PNG o WebP.');

            return;
        }

        if (! $value->isValid()) {
            Log::warning('[ValidRasterImage] upload rejected', [
                'error' => $value->getError(),
                'client_mime' => $value->getClientMimeType(),
                'client_name' => $value->getClientOriginalName(),
            ]);
            $fail($this->uploadErrorMessage($value->getError()));

            return;
        }

        $mime = $this->detectMime($value);
        if (! is_string($mime) || ! in_array($mime, $this->allowedMimes, true)) {
            Log::warning('[ValidRasterImage] mime rejected', [
                'mime' => $mime,
                'client_mime' => $value->getClientMimeType(),
                'client_name' => $value->getClientOriginalName(),
            ]);
            $fail('El logo debe ser una imagen JPEG, PNG o WebP.');
        }
    }

    private function detectMime(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            return null;
        }

        $info = @getimagesize($path);
        if (is_array($info) && isset($info['mime']) && is_string($info['mime'])) {
            return $info['mime'];
        }

        if (! class_exists(\finfo::class)) {
            return $file->getMimeType();
        }

        $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        return is_string($detected) && $detected !== '' ? $detected : null;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El logo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'La carga del logo se interrumpió. Intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene carpeta temporal para archivos.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo guardar el logo en el servidor.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la carga del logo.',
            default => 'No se pudo cargar el logo. Intenta de nuevo.',
        };
    }
}
