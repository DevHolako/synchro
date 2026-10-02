<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\DeleteExamAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExamDestroyController extends Controller
{
    public function __invoke(Exam $exam, DeleteExamAction $action): RedirectResponse
    {
        Gate::authorize('delete', $exam);

        $action->execute($exam);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.exam_deleted')]);

        return back();
    }
}
