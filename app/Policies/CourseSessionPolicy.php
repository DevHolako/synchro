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
     * Determine whether the user can browse any group's, teacher's, room's or campus's timetable,
     * not only their own.
     */
    public function browse(User $user): bool
    {
        return $user->hasPermission(Permission::BrowseSchedules);
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
     * Determine whether the user can take a session's attendance: the session's own teacher,
     * or anyone who manages schedules.
     */
    public function recordAttendance(User $user, CourseSession $session): bool
    {
        return $user->hasPermission(Permission::RecordAttendance)
            && ($session->teacher_id === $user->id || $user->hasPermission(Permission::ManageSchedules));
    }

    /**
     * Determine whether the user can remove a session.
     */
    public function delete(User $user, CourseSession $session): bool
    {
        return $user->hasPermission(Permission::ManageSchedules);
    }
}
