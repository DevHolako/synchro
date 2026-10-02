<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\AllocateExamRoomsAction;
use App\Actions\Exams\ShowExamAllocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\AllocateExamRoomsRequest;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;

/**
 * Saves an exam's rooms and answers with its new split (JSON).
 */
class ExamRoomsUpdateController extends Controller
{
    public function __invoke(
        AllocateExamRoomsRequest $request,
        Exam $exam,
        AllocateExamRoomsAction $allocate,
        ShowExamAllocationAction $show,
    ): JsonResponse {
        $allocate->execute($exam, $request->roomIds(), $request->softConflictOverride());

        return new JsonResponse($show->execute($exam->refresh()));
    }
}
