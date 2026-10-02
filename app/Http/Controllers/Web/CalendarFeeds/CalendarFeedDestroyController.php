<?php

namespace App\Http\Controllers\Web\CalendarFeeds;

use App\Actions\CalendarFeeds\RevokeCalendarFeedTokenAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarFeedDestroyController extends Controller
{
    public function __invoke(Request $request, RevokeCalendarFeedTokenAction $action): RedirectResponse
    {
        $action->execute($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.calendar_feed_revoked')]);

        return back();
    }
}
