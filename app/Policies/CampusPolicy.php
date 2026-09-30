<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;

class CampusPolicy
{
    /**
     * Determine whether the user can view any campuses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the campus.
     */
    public function view(User $user, Campus $campus): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create campuses.
     */
    public function create(User $user): bool
    {
        return $user->canManageReferentials();
    }

    /**
     * Determine whether the user can update the campus.
     */
    public function update(User $user, Campus $campus): bool
    {
        return $user->canManageReferentials();
    }

    /**
     * Determine whether the user can delete/deactivate the campus.
     */
    public function delete(User $user, Campus $campus): bool
    {
        return $user->canManageReferentials();
    }
}
