<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\AssignInvigilatorsAction;
use App\Actions\Exams\ShowExamAllocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\AssignInvigilatorsRequest;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use Illuminate\Http\JsonResponse;

/**
 * Staffs one exam room and answers with the exam's allocation (JSON).
 */
class ExamInvigilatorsUpdateController extends Controller
{
    public function __invoke(
        AssignInvigilatorsRequest $request,
        Exam $exam,
        ExamRoomAssignment $assignment,
        AssignInvigilatorsAction $assign,
        ShowExamAllocationAction $show,
    ): JsonResponse {
        $assign->execute($assignment, $request->leadId(), $request->assistantIds(), $request->softConflictOverride());

        return new JsonResponse($show->execute($exam->refresh()));
    }
}
