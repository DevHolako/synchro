<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Campus;
use App\Models\User;

class CampusPolicy
{
    /**
     * Determine whether the user can view any campuses.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewCampuses)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can view the campus.
     */
    public function view(User $user, Campus $campus): bool
    {
        return $user->hasPermission(Permission::ViewCampuses)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can create campuses.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::CreateCampuses)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can update the campus.
     */
    public function update(User $user, Campus $campus): bool
    {
        return $user->hasPermission(Permission::UpdateCampuses)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can delete/deactivate the campus.
     */
    public function delete(User $user, Campus $campus): bool
    {
        return $user->hasPermission(Permission::DeleteCampuses)
            || $user->hasPermission(Permission::ManageReferentials);
    }
}
