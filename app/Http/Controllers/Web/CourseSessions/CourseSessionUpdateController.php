<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\UpdateCourseSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\UpdateCourseSessionRequest;
use App\Models\CourseSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CourseSessionUpdateController extends Controller
{
    public function __invoke(
        UpdateCourseSessionRequest $request,
        CourseSession $session,
        UpdateCourseSessionAction $action,
    ): RedirectResponse {
        $action->execute($session, $request->payload(), $request->softConflictOverride());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.course_session_updated')]);

        return back();
    }
}
