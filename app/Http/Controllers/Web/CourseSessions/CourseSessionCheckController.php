<?php

namespace App\Http\Controllers\Web\CourseSessions;

use App\Actions\CourseSessions\CreateCourseSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseSessions\CheckCourseSessionRequest;
use App\Services\Scheduling\ConflictDetectorService;
use Illuminate\Http\JsonResponse;

/**
 * Reports the conflicts a session would cause, without saving anything (for drag-and-drop previews).
 */
class CourseSessionCheckController extends Controller
{
    public function __invoke(CheckCourseSessionRequest $request, ConflictDetectorService $detector): JsonResponse
    {
        $slot = CreateCourseSessionAction::slot($request->payload(), $request->ignoreSessionId());

        return new JsonResponse($detector->checkConflicts($slot)->toArray());
    }
}
