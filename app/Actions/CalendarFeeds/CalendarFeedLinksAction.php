<?php

namespace App\Actions\CalendarFeeds;

use App\Models\User;

/**
 * The user's subscription link, as https (Google) and webcal (Apple, Outlook) URLs, or null without one.
 */
class CalendarFeedLinksAction
{
    /**
     * @return array{https: string, webcal: string}|null
     */
    public function execute(User $user): ?array
    {
        if ($user->calendar_feed_token === null) {
            return null;
        }

        $url = route('calendar-feeds.show', ['token' => $user->calendar_feed_token]);

        return ['https' => $url, 'webcal' => (string) preg_replace('#^https?://#', 'webcal://', $url)];
    }
}
