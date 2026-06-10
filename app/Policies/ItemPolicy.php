<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        // Admin, Staff, and IT Support can all read the item master.
        return $user->isAdmin() || $user->isStaff() || $user->isItSupport();
    }

    public function view(User $user, Item $item): bool
    {
        return $user->isAdmin() || $user->isStaff() || $user->isItSupport();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Item $item): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->isAdmin();
    }
}
