<?php

declare(strict_types=1);

namespace App\Application\ServiceOrder;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class TechnicianCredentialAuthenticator
{
    public function __construct(
        private readonly TechnicianAccountProvisioner $provisioner,
    ) {}

    public function authenticate(string $email, string $documentNumber): User
    {
        $staff = $this->findActiveTechnician($email, $documentNumber);
        if ($staff === null) {
            Log::warning('[FIX] Technician credential mismatch', [
                'email' => mb_strtolower(trim($email)),
            ]);

            throw new InvalidArgumentException('No encontramos un técnico activo con ese correo y cédula.');
        }

        $user = $this->provisioner->ensureUser($staff);

        Log::info('[FIX] Technician authenticated with email and cédula', [
            'staff_id' => $staff->id,
            'user_id' => $user->id,
        ]);

        return $user;
    }

    public function findActiveTechnician(string $email, string $documentNumber): ?Staff
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $normalizedDocument = self::normalizeDocument($documentNumber);

        if ($normalizedEmail === '' || $normalizedDocument === '') {
            return null;
        }

        return Staff::query()
            ->where('role', 'tecnico')
            ->where('status', 'activo')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(document_number, '.', ''), '-', ''), ' ', '') = ?",
                [$normalizedDocument],
            )
            ->first();
    }

    public static function normalizeDocument(string $document): string
    {
        return preg_replace('/[\s.\-]/', '', trim($document)) ?? '';
    }
}
