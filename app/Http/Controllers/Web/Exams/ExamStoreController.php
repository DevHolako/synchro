<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\SaveExamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\StoreExamRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Drafts an exam: it books nothing until it is scheduled.
 */
class ExamStoreController extends Controller
{
    public function __invoke(StoreExamRequest $request, SaveExamAction $action): RedirectResponse
    {
        $action->execute(new Exam, $request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_created')]);

        return back();
    }
}
