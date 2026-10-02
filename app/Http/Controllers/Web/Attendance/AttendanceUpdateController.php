<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Actions\Attendance\RecordAttendanceAction;
use App\Actions\Attendance\ShowAttendanceRegisterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\RecordAttendanceRequest;
use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;

class AttendanceUpdateController extends Controller
{
    public function __invoke(
        RecordAttendanceRequest $request,
        CourseSession $session,
        RecordAttendanceAction $record,
        ShowAttendanceRegisterAction $show,
    ): JsonResponse {
        $record->execute($session, $request->marks(), $request->user());

        return new JsonResponse([
            'message' => __('messages.attendance_saved'),
            'students' => $show->execute($session),
        ]);
    }
}
