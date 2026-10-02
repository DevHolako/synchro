<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\DeleteExamPeriodAction;
use App\Http\Controllers\Controller;
use App\Models\ExamPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExamPeriodDestroyController extends Controller
{
    public function __invoke(ExamPeriod $examPeriod, DeleteExamPeriodAction $action): RedirectResponse
    {
        Gate::authorize('delete', $examPeriod);

        $action->execute($examPeriod);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_period_deleted')]);

        return to_route('exams.index');
    }
}
