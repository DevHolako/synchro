<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\RescheduleCourseSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\RescheduleCourseSessionRequest;
use App\Models\CourseSession;
use Illuminate\Http\Response;

/**
 * A calendar drop or resize, as JSON: 204 when saved, 422 (hard) or 409 (soft) conflicts otherwise,
 * so the calendar can snap back or ask for an override.
 */
class CourseSessionRescheduleController extends Controller
{
    public function __invoke(
        RescheduleCourseSessionRequest $request,
        CourseSession $session,
        RescheduleCourseSessionAction $action,
    ): Response {
        $action->execute($session, $request->times(), $request->softConflictOverride());

        return response()->noContent();
    }
}
