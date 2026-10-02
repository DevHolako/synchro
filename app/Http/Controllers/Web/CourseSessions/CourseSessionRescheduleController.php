<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\RescheduleCourseSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\RescheduleCourseSessionRequest;
use App\Http\Resources\TimetableSessionResource;
use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;

/**
 * A calendar drop or resize: JSON in and out, so the calendar can snap back or ask for an override.
 */
class CourseSessionRescheduleController extends Controller
{
    public function __invoke(
        RescheduleCourseSessionRequest $request,
        CourseSession $session,
        RescheduleCourseSessionAction $action,
    ): JsonResponse {
        $session = $action->execute($session, $request->times(), $request->softConflictOverride());
        $session->load(['module', 'teacher', 'room.building', 'studentGroups', 'conflictOverrides']);

        return new JsonResponse([
            'message' => __('messages.course_session_rescheduled'),
            'session' => (new TimetableSessionResource($session))->resolve(),
        ]);
    }
}
