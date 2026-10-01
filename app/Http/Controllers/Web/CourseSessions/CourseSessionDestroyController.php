<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\DeleteCourseSessionAction;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseSessionDestroyController extends Controller
{
    public function __invoke(CourseSession $session, DeleteCourseSessionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $session);

        $action->execute($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.course_session_deleted')]);

        return back();
    }
}
