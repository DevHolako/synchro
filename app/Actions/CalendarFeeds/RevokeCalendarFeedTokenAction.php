<?php

namespace App\Actions\CalendarFeeds;

use App\Models\User;

/**
 * Turns the user's feed off: calendar apps subscribed to it stop receiving updates.
 */
class RevokeCalendarFeedTokenAction
{
    public function execute(User $user): void
    {
        $user->forceFill(['calendar_feed_token' => null, 'calendar_feed_token_hash' => null])->save();
    }
}
