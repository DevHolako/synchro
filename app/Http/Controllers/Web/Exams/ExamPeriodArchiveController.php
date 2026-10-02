<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\ArchiveExamPeriodAction;
use App\Http\Controllers\Controller;
use App\Models\ExamPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExamPeriodArchiveController extends Controller
{
    public function __invoke(ExamPeriod $examPeriod, ArchiveExamPeriodAction $action): RedirectResponse
    {
        Gate::authorize('update', $examPeriod);

        $count = $action->execute($examPeriod);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_period_archived', ['count' => $count])]);

        return back();
    }
}
