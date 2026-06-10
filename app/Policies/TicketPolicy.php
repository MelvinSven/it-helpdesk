<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isItSupport() || $user->isStaff();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        // Any authenticated role may view any ticket's details.
        // Participation in the conversation is gated separately by comment().
        return $user->isAdmin() || $user->isStaff() || $user->isItSupport();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff() || $user->isItSupport();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isItSupport() && $ticket->assignee_id === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        // Only the Pelapor (requestor) and Penangan (assignee) may join the
        // conversation. Admins retain access for oversight. Everyone else can
        // view the ticket (see view()) but cannot reply.
        return $user->isAdmin()
            || $ticket->requestor_id === $user->id
            || $ticket->assignee_id === $user->id;
    }
}
