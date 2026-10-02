<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\CreateExamPeriodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\StoreExamPeriodRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExamPeriodStoreController extends Controller
{
    public function __invoke(StoreExamPeriodRequest $request, CreateExamPeriodAction $action): RedirectResponse
    {
        $period = $action->execute($request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_period_created')]);

        return to_route('exams.index', ['period' => $period->id]);
    }
}
