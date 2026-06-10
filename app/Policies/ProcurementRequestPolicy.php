<?php

namespace App\Policies;

use App\Models\ProcurementRequest;
use App\Models\User;

class ProcurementRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ProcurementRequest $procurementRequest): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ProcurementRequest $procurementRequest): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ProcurementRequest $procurementRequest): bool
    {
        return $user->isAdmin();
    }
}
