<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Determine whether the user can view any rooms.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewRooms)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can view the room.
     */
    public function view(User $user, Room $room): bool
    {
        return $user->hasPermission(Permission::ViewRooms)
            || $user->hasPermission(Permission::ViewReferentials);
    }

    /**
     * Determine whether the user can create rooms.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::CreateRooms)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can update the room.
     */
    public function update(User $user, Room $room): bool
    {
        return $user->hasPermission(Permission::UpdateRooms)
            || $user->hasPermission(Permission::ManageReferentials);
    }

    /**
     * Determine whether the user can delete/deactivate the room.
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->hasPermission(Permission::DeleteRooms)
            || $user->hasPermission(Permission::ManageReferentials);
    }
}
