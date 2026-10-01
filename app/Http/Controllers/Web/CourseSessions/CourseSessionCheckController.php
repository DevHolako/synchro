<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\CheckSessionConflictsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\CheckCourseSessionRequest;
use Illuminate\Http\JsonResponse;

/**
 * Reports the conflicts a session would cause, without saving anything (for drag-and-drop previews).
 */
class CourseSessionCheckController extends Controller
{
    public function __invoke(CheckCourseSessionRequest $request, CheckSessionConflictsAction $action): JsonResponse
    {
        return new JsonResponse($action->execute($request->payload(), $request->ignoreSessionId())->toArray());
    }
}
