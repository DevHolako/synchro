<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewDepartments)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view(User $user, Department $department): bool
    {
        return $user->hasPermission(Permission::ViewDepartments)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::CreateDepartments)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->hasPermission(Permission::UpdateDepartments)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermission(Permission::DeleteDepartments)
            || $user->hasPermission(Permission::ManageReferentials);
    }
}
