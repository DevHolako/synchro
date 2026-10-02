<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Actions\Attendance\RecordAttendanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\RecordAttendanceRequest;
use App\Models\CourseSession;
use Illuminate\Http\Response;

class AttendanceUpdateController extends Controller
{
    public function __invoke(RecordAttendanceRequest $request, CourseSession $session, RecordAttendanceAction $record): Response
    {
        $record->execute($session, $request->marks(), $request->user());

        return response()->noContent();
    }
}
