<?php

namespace App\Http\Controllers\Web\CalendarFeeds;

use App\Actions\CalendarFeeds\BuildCalendarFeedAction;
use App\Actions\CalendarFeeds\FindCalendarFeedOwnerAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * A private iCal subscription (ADR 0010): the token in the URL is the only credential.
 */
class CalendarFeedShowController extends Controller
{
    public function __invoke(string $token, FindCalendarFeedOwnerAction $findOwner, BuildCalendarFeedAction $build): Response
    {
        $user = $findOwner->execute($token);

        abort_if($user === null, 404);

        return new Response($build->execute($user), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="synchro.ics"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
