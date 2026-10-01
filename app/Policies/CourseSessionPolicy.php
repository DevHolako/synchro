<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\CourseSession;
use App\Models\User;

class CourseSessionPolicy
{
    /**
     * Determine whether the user can read course sessions (timetables).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewSchedules)
            || $user->hasPermission(Permission::ManageSchedules);
    }

    /**
     * Determine whether the user can schedule sessions or run conflict checks.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSchedules);
    }

    /**
     * Determine whether the user can move or re-staff a session.
     */
    public function update(User $user, CourseSession $session): bool
    {
        return $user->hasPermission(Permission::ManageSchedules);
    }

    /**
     * Determine whether the user can remove a session.
     */
    public function delete(User $user, CourseSession $session): bool
    {
        return $user->hasPermission(Permission::ManageSchedules);
    }
}
