<?php

namespace App\Http\Controllers\Web\Grades;

use App\Actions\Grades\SaveGradesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Grades\SaveGradesRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExamGradesUpdateController extends Controller
{
    public function __invoke(SaveGradesRequest $request, Exam $exam, SaveGradesAction $action): RedirectResponse
    {
        $action->execute($exam, $request->lines());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.grades_saved')]);

        return back();
    }
}
