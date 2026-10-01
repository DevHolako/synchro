<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can list provisioned accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewUsers)
            || $user->hasPermission(Permission::ManageUsers);
    }

    /**
     * Determine whether the user can provision (create and invite) accounts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ProvisionUsers)
            || $user->hasPermission(Permission::ManageUsers);
    }

    /**
     * Determine whether the user can regenerate and resend an Invitation Token.
     */
    public function resendInvitation(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::ProvisionUsers)
            || $user->hasPermission(Permission::ManageUsers);
    }

    /**
     * Determine whether the user can issue a temporary password for another account.
     */
    public function issueTemporaryPassword(User $user, User $target): bool
    {
        return $user->isNot($target) && $user->hasPermission(Permission::ManageUsers);
    }
}
