<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\PublishExamAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExamPublishController extends Controller
{
    public function __invoke(Request $request, Exam $exam, PublishExamAction $action): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $action->execute($exam, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_published')]);

        return back();
    }
}
