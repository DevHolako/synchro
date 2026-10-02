<?php

namespace App\Http\Controllers\Web\Grades;

use App\Actions\Grades\OpenGradeSheetAction;
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
 * otherwise. Only the teacher's visit opens the sheet; others read it once it exists.
 */
class ExamGradesShowController extends Controller
{
    public function __invoke(Request $request, Exam $exam, OpenGradeSheetAction $open, ShowGradeSheetAction $show): Response|RedirectResponse
    {
        Gate::authorize('viewGrades', $exam);

        $viewer = $request->user();
        $sheet = $viewer->can('enterGrades', $exam) ? $open->execute($exam) : $exam->deliberation;

        if ($sheet === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('messages.grade_sheet_not_opened')]);

            return back();
        }

        return Inertia::render('grades/show', $show->execute($exam, $sheet, $viewer));
    }
}
