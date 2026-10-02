<?php

namespace App\Http\Controllers\Web\CalendarFeeds;

use App\Actions\CalendarFeeds\IssueCalendarFeedTokenAction;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Creates the user's calendar link, or replaces it (the old link stops working).
 */
class CalendarFeedStoreController extends Controller
{
    public function __invoke(Request $request, IssueCalendarFeedTokenAction $action): RedirectResponse
    {
        Gate::authorize('viewAny', CourseSession::class);

        $action->execute($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.calendar_feed_issued')]);

        return back();
    }
}
