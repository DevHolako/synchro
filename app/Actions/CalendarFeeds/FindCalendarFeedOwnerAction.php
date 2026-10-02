<?php

namespace App\Actions\CalendarFeeds;

use App\Models\CourseSession;
use App\Models\User;

/**
 * Whose feed a token opens: an active account that may still read timetables, or nobody.
 */
class FindCalendarFeedOwnerAction
{
    public function execute(string $token): ?User
    {
        $user = User::query()->where('calendar_feed_token_hash', User::hashCalendarFeedToken($token))->first();

        if ($user === null || ! $user->isActive() || ! $user->can('viewAny', CourseSession::class)) {
            return null;
        }

        return $user;
    }
}
