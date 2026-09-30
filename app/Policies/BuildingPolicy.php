<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    /**
     * Determine whether the user can view any buildings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the building.
     */
    public function view(User $user, Building $building): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create buildings.
     */
    public function create(User $user): bool
    {
        return $user->canManageReferentials();
    }

    /**
     * Determine whether the user can update the building.
     */
    public function update(User $user, Building $building): bool
    {
        return $user->canManageReferentials();
    }

    /**
     * Determine whether the user can delete/deactivate the building.
     */
    public function delete(User $user, Building $building): bool
    {
        return $user->canManageReferentials();
    }
}
