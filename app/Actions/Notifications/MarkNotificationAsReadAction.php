<?php

namespace App\Actions\Notifications;

use App\Models\User;

class MarkNotificationAsReadAction
{
    /**
     * Mark a specific notification as read for the user.
     */
    public function execute(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();

            return true;
        }

        return false;
    }
}
