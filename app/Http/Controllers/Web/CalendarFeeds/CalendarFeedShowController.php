<?php

namespace App\Http\Controllers\Web\CalendarFeeds;

use App\Actions\CalendarFeeds\BuildCalendarFeedAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * A private iCal subscription (ADR 0010): the token in the URL is the only credential.
 */
class CalendarFeedShowController extends Controller
{
    public function __invoke(string $token, BuildCalendarFeedAction $action): Response
    {
        $user = User::query()->where('calendar_feed_token_hash', hash('sha256', $token))->first();

        abort_if($user === null || ! $user->isActive(), 404);

        return new Response($action->execute($user), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="synchro.ics"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
