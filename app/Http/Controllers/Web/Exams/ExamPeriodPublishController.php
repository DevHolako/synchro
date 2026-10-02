<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\PublishPeriodExamsAction;
use App\Http\Controllers\Controller;
use App\Models\ExamPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Publishes every scheduled exam of a period at once.
 */
class ExamPeriodPublishController extends Controller
{
    public function __invoke(Request $request, ExamPeriod $examPeriod, PublishPeriodExamsAction $action): RedirectResponse
    {
        Gate::authorize('update', $examPeriod);

        $count = $action->execute($examPeriod, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_period_published', ['count' => $count])]);

        return back();
    }
}
