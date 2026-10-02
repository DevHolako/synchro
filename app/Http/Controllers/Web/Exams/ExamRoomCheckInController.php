<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\ShowRoomCheckInAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A room's check-in list, for its invigilators and exam managers.
 */
class ExamRoomCheckInController extends Controller
{
    public function __invoke(Exam $exam, ExamRoomAssignment $assignment, ShowRoomCheckInAction $action): Response
    {
        Gate::authorize('checkInRoom', [$exam, $assignment]);

        return Inertia::render('exams/room-check-in', $action->execute($assignment));
    }
}
