<?php

namespace App\Policies;

use App\Models\BorrowRecord;
use App\Models\User;

class BorrowRecordPolicy
{
    public function return(User $user, BorrowRecord $borrow): bool
    {
        return $user->isAdmin() || $user->id === $borrow->borrower_id;
    }

    public function delete(User $user, BorrowRecord $borrow): bool
    {
        // Only system admins may delete borrow records.
        return $user->isAdmin();
    }
}
