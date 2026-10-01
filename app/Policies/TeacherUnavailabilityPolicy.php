<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\TeacherUnavailability;
use App\Models\User;

class TeacherUnavailabilityPolicy
{
    /**
     * Determine whether the user can list and declare their own unavailabilities.
     */
    public function declare(User $user): bool
    {
        return $user->hasPermission(Permission::DeclareUnavailability);
    }

    /**
     * Determine whether the user can edit their own unavailability.
     */
    public function update(User $user, TeacherUnavailability $unavailability): bool
    {
        return $user->hasPermission(Permission::DeclareUnavailability)
            && $unavailability->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can withdraw (delete) their own unavailability.
     */
    public function delete(User $user, TeacherUnavailability $unavailability): bool
    {
        return $user->hasPermission(Permission::DeclareUnavailability)
            && $unavailability->teacher_id === $user->id;
    }

    /**
     * Determine whether the user can list every teacher's unavailabilities for review.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ReviewUnavailability);
    }

    /**
     * Determine whether the user can approve or reject an unavailability.
     */
    public function review(User $user, TeacherUnavailability $unavailability): bool
    {
        return $user->hasPermission(Permission::ReviewUnavailability);
    }
}
