<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Actions\Attendance\ShowAttendanceRegisterAction;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * A session's attendance register, for the register sheet on the timetable.
 */
class AttendanceShowController extends Controller
{
    public function __invoke(CourseSession $session, ShowAttendanceRegisterAction $action): JsonResponse
    {
        Gate::authorize('recordAttendance', $session);

        return new JsonResponse(['students' => $action->execute($session)]);
    }
}
