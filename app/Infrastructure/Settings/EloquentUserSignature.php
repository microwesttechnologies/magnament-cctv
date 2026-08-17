<?php

declare(strict_types=1);

namespace App\Infrastructure\Settings;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class EloquentUserSignature
{
    /**
     * @return array{path: ?string, url: ?string}
     */
    public function snapshot(User $user): array
    {
        $path = is_string($user->signature_path) && $user->signature_path !== ''
            ? $user->signature_path
            : null;

        return [
            'path' => $path,
            'url' => $path !== null && Storage::disk('public')->exists($path)
                ? asset('storage/'.$path)
                : null,
        ];
    }

    public function storeFromUpload(User $user, UploadedFile $file): void
    {
        $this->deleteFile($user);
        $path = $file->store('user_signatures/'.$user->id, 'public');
        $user->signature_path = $path;
        $user->save();
    }

    public function storeFromBase64(User $user, string $dataUri): void
    {
        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $dataUri, $matches)) {
            throw new InvalidArgumentException('Formato de firma no válido.');
        }

        $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);
        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('No se pudo decodificar la firma.');
        }

        $this->deleteFile($user);
        $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $path = 'user_signatures/'.$user->id.'/signature-'.now()->format('YmdHis').'.'.$extension;
        Storage::disk('public')->put($path, $binary);
        $user->signature_path = $path;
        $user->save();
    }

    public function delete(User $user): void
    {
        $this->deleteFile($user);
        $user->signature_path = null;
        $user->save();
    }

    public function dataUriFromPath(?string $path): ?string
    {
        if ($path === null || $path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        $binary = Storage::disk('public')->get($path);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function deleteFile(User $user): void
    {
        $current = $user->signature_path;
        if (is_string($current) && $current !== '' && Storage::disk('public')->exists($current)) {
            Storage::disk('public')->delete($current);
        }
    }
}
