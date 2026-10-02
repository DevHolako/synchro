<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\ShowExamAllocationAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * An exam's rooms, split and invigilators, with the rooms and teachers to pick from (JSON).
 */
class ExamAllocationShowController extends Controller
{
    public function __invoke(Exam $exam, ShowExamAllocationAction $action): JsonResponse
    {
        Gate::authorize('update', $exam);

        return new JsonResponse($action->execute($exam));
    }
}
