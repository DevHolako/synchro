<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\BatchCreateCourseSessionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\StoreCourseSessionBatchRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CourseSessionBatchStoreController extends Controller
{
    public function __invoke(StoreCourseSessionBatchRequest $request, BatchCreateCourseSessionsAction $action): RedirectResponse
    {
        $count = count($action->execute($request->payload(), $request->softConflictOverride()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice('messages.course_sessions_batch_created', $count, ['count' => $count]),
        ]);

        return back();
    }
}
