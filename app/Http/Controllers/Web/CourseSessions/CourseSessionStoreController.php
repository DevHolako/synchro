<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\CreateCourseSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\StoreCourseSessionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CourseSessionStoreController extends Controller
{
    public function __invoke(StoreCourseSessionRequest $request, CreateCourseSessionAction $action): RedirectResponse
    {
        $action->execute($request->payload(), $request->softConflictOverride());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.course_session_created')]);

        return back();
    }
}
