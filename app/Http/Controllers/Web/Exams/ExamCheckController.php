<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\CheckExamConflictsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\CheckExamRequest;
use Illuminate\Http\JsonResponse;

/**
 * Reports the conflicts an exam would cause once scheduled, without saving anything.
 */
class ExamCheckController extends Controller
{
    public function __invoke(CheckExamRequest $request, CheckExamConflictsAction $action): JsonResponse
    {
        return new JsonResponse($action->execute($request->payload(), $request->ignoreExamId())->toArray());
    }
}
