<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    /**
     * Determine whether the user can view any modules.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewModules)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can view the module.
     */
    public function view(User $user, Module $module): bool
    {
        return $user->hasPermission(Permission::ViewModules)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can create modules.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::CreateModules)
            || $user->hasPermission(Permission::ManageModules)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can update the module.
     */
    public function update(User $user, Module $module): bool
    {
        return $user->hasPermission(Permission::UpdateModules)
            || $user->hasPermission(Permission::ManageModules)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can delete the module.
     */
    public function delete(User $user, Module $module): bool
    {
        return $user->hasPermission(Permission::DeleteModules)
            || $user->hasPermission(Permission::ManageModules)
            || $user->hasPermission(Permission::ManageReferentials);
    }
}
