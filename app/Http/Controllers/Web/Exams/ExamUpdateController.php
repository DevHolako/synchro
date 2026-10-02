<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\UpdateExamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\UpdateExamRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExamUpdateController extends Controller
{
    public function __invoke(UpdateExamRequest $request, Exam $exam, UpdateExamAction $action): RedirectResponse
    {
        $action->execute($exam, $request->payload());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_updated')]);

        return back();
    }
}
