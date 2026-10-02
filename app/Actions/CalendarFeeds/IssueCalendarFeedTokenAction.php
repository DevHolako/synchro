<?php

namespace App\Actions\CalendarFeeds;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Gives the user a new private feed token, which revokes any previous one (ADR 0010).
 */
class IssueCalendarFeedTokenAction
{
    private const int TOKEN_LENGTH = 48;

    public function execute(User $user): string
    {
        $token = Str::random(self::TOKEN_LENGTH);

        $user->forceFill([
            'calendar_feed_token' => $token,
            'calendar_feed_token_hash' => hash('sha256', $token),
        ])->save();

        return $token;
    }
}
