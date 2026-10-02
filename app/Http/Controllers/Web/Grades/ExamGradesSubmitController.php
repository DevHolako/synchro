<?php

namespace App\Http\Controllers\Web\Grades;

use App\Actions\Grades\SubmitGradeSheetAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExamGradesSubmitController extends Controller
{
    public function __invoke(Request $request, Exam $exam, SubmitGradeSheetAction $action): RedirectResponse
    {
        Gate::authorize('enterGrades', $exam);

        $action->execute($exam, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.grade_sheet_submitted')]);

        return back();
    }
}
