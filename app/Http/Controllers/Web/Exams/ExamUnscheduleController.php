<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\ChangeExamStateAction;
use App\Enums\ExamState;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Sends a scheduled exam back to Draft, releasing what it booked.
 */
class ExamUnscheduleController extends Controller
{
    public function __invoke(Exam $exam, ChangeExamStateAction $action): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $action->execute($exam, ExamState::Draft);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_unscheduled')]);

        return back();
    }
}
