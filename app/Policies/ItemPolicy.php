<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        // Item master is admin-only. Staff and IT Support never browse the
        // catalog; they reach items solely through the borrow form, which
        // loads available items independently of these routes.
        return $user->isAdmin();
    }

    public function view(User $user, Item $item): bool
    {
        return $user->isAdmin();
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
