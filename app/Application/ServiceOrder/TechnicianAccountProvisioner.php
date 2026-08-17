<?php

declare(strict_types=1);

namespace App\Application\ServiceOrder;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TechnicianAccountProvisioner
{
    public function ensureUser(Staff $staff): User
    {
        if (! $staff->isActiveTechnician()) {
            throw new InvalidArgumentException('El personal no es un técnico activo.');
        }

        if (! is_string($staff->email) || $staff->email === '') {
            throw new InvalidArgumentException('El técnico no tiene correo registrado.');
        }

        if ($staff->user_id) {
            $user = User::query()->find($staff->user_id);
            if ($user instanceof User) {
                if (! $user->isTechnician()) {
                    $user->role = 'tecnico';
                    $user->save();
                }

                return $user;
            }
        }

        $existing = User::query()->where('email', $staff->email)->first();
        if ($existing instanceof User) {
            if (! $existing->isTechnician()) {
                throw new InvalidArgumentException('Ese correo ya pertenece a una cuenta de oficina.');
            }
            $staff->user_id = $existing->id;
            $staff->save();

            return $existing;
        }

        $user = User::query()->create([
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'tecnico',
            'state' => 'active',
            'password' => Str::password(40),
        ]);

        $staff->user_id = $user->id;
        $staff->save();

        return $user;
    }
}
