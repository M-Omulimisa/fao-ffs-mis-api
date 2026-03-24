<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Reserved for future FAO FFS-specific post-creation logic.
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Reserved for future FAO FFS-specific post-update logic.
    }
}
