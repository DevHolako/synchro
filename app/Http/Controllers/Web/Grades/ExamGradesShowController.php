<?php

namespace App\Http\Controllers\Web\Grades;

use App\Actions\Grades\OpenGradeSheetAction;
use App\Actions\Grades\ShowGradeSheetAction;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An exam's grading grid: editable by the module teacher while it is a draft, read-only otherwise.
 */
class ExamGradesShowController extends Controller
{
    public function __invoke(Request $request, Exam $exam, OpenGradeSheetAction $open, ShowGradeSheetAction $show): Response
    {
        Gate::authorize('viewGrades', $exam);

        return Inertia::render('grades/show', $show->execute($exam, $open->execute($exam), $request->user()));
    }
}
