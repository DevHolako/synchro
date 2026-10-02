<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\UpdateExamPeriodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\UpdateExamPeriodRequest;
use App\Models\ExamPeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExamPeriodUpdateController extends Controller
{
    public function __invoke(UpdateExamPeriodRequest $request, ExamPeriod $examPeriod, UpdateExamPeriodAction $action): RedirectResponse
    {
        $action->execute($examPeriod, $request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_period_updated')]);

        return back();
    }
}
