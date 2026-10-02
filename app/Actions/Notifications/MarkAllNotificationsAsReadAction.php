<?php

namespace App\Actions\Notifications;

use App\Models\User;

class MarkAllNotificationsAsReadAction
{
    /**
     * Mark all unread notifications as read for the user.
     */
    public function execute(User $user): int
    {
        $unread = $user->unreadNotifications;
        $count = $unread->count();

        $unread->markAsRead();

        return $count;
    }
}
