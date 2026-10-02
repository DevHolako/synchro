<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\UnscheduleExamAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExamUnscheduleController extends Controller
{
    public function __invoke(Exam $exam, UnscheduleExamAction $action): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $action->execute($exam);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_unscheduled')]);

        return back();
    }
}
