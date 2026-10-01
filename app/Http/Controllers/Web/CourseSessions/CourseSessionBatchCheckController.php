<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\CheckCourseSessionBatchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\CheckCourseSessionBatchRequest;
use Illuminate\Http\JsonResponse;

/**
 * Reports each slot's conflicts and the syllabus impact of a batch, without saving anything.
 */
class CourseSessionBatchCheckController extends Controller
{
    public function __invoke(CheckCourseSessionBatchRequest $request, CheckCourseSessionBatchAction $action): JsonResponse
    {
        return new JsonResponse($action->execute($request->payload()));
    }
}
