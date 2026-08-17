<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\User;

final class ServiceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOffice() || $user->isTechnician();
    }

    public function view(User $user, ServiceOrder $order): bool
    {
        if ($user->isOffice()) {
            return true;
        }

        $staffId = $user->staff?->id;

        return $staffId !== null && (int) $order->staff_id === (int) $staffId;
    }

    public function create(User $user): bool
    {
        return $user->isOffice();
    }

    public function assign(User $user, ServiceOrder $order): bool
    {
        return $user->isOffice();
    }

    public function reassign(User $user, ServiceOrder $order): bool
    {
        return $user->isOffice();
    }

    public function updatePriority(User $user, ServiceOrder $order): bool
    {
        return $user->isOffice() && ! $order->statusEnum()->isTerminal();
    }

    public function cancel(User $user, ServiceOrder $order): bool
    {
        if ($user->isOffice()) {
            return $order->statusEnum()->canCancel();
        }

        $staffId = $user->staff?->id;

        return $staffId !== null
            && (int) $order->staff_id === (int) $staffId
            && $order->statusEnum()->canCancel();
    }

    public function start(User $user, ServiceOrder $order): bool
    {
        $staffId = $user->staff?->id;

        return $user->isTechnician()
            && $staffId !== null
            && (int) $order->staff_id === (int) $staffId
            && $order->statusEnum()->canStart();
    }

    public function resolve(User $user, ServiceOrder $order): bool
    {
        $staffId = $user->staff?->id;

        return $user->isTechnician()
            && $staffId !== null
            && (int) $order->staff_id === (int) $staffId
            && $order->statusEnum()->canResolve();
    }

    public function finalize(User $user, ServiceOrder $order): bool
    {
        return $this->resolve($user, $order);
    }

    public function addEvidence(User $user, ServiceOrder $order): bool
    {
        if ($order->statusEnum()->isTerminal()) {
            return false;
        }

        if ($user->isOffice()) {
            return true;
        }

        $staffId = $user->staff?->id;

        return $staffId !== null && (int) $order->staff_id === (int) $staffId;
    }
}
