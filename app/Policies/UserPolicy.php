<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the profile can be viewed.
     */
    public function view(?User $viewer, User $profile): bool
    {
        return $profile->is_profile_public
            || $viewer?->is($profile)
            || $viewer?->isAdmin() === true;
    }

    /**
     * Determine whether the profile can be updated.
     */
    public function update(User $user, User $profile): bool
    {
        return $user->is($profile) || $user->isAdmin();
    }
}
