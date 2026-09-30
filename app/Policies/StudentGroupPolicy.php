<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\StudentGroup;
use App\Models\User;

class StudentGroupPolicy
{
    /**
     * Determine whether the user can view any student groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewStudentGroups)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can view the student group.
     */
    public function view(User $user, StudentGroup $studentGroup): bool
    {
        return $user->hasPermission(Permission::ViewStudentGroups)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can create student groups.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::CreateStudentGroups)
            || $user->hasPermission(Permission::ManagePrograms)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can update the student group.
     */
    public function update(User $user, StudentGroup $studentGroup): bool
    {
        return $user->hasPermission(Permission::UpdateStudentGroups)
            || $user->hasPermission(Permission::ManagePrograms)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can delete the student group.
     */
    public function delete(User $user, StudentGroup $studentGroup): bool
    {
        return $user->hasPermission(Permission::DeleteStudentGroups)
            || $user->hasPermission(Permission::ManagePrograms)
            || $user->hasPermission(Permission::ManageReferentials);
    }
}
