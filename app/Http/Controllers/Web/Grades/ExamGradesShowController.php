<?php

namespace App\Http\Controllers\Web\Grades;

use App\Actions\Grades\FindGradeSheetAction;
use App\Actions\Grades\ShowGradeSheetAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An exam's grading grid: editable by the module teacher while it is a draft, read-only
 * otherwise, and not shown to others before its teacher opens it.
 */
class ExamGradesShowController extends Controller
{
    public function __invoke(Request $request, Exam $exam, FindGradeSheetAction $find, ShowGradeSheetAction $show): Response|RedirectResponse
    {
        Gate::authorize('viewGrades', $exam);

        $sheet = $find->execute($exam, $request->user());

        if ($sheet === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('messages.grade_sheet_not_opened')]);

            return back();
        }

        return Inertia::render('grades/show', $show->execute($exam, $sheet, $request->user()));
    }
}
