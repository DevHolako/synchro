<?php

namespace App\Http\Controllers\Web\Deliberations;

use App\Actions\Grades\ReturnGradeSheetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Grades\ReturnGradeSheetRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class GradeSheetReturnController extends Controller
{
    public function __invoke(ReturnGradeSheetRequest $request, Exam $exam, ReturnGradeSheetAction $action): RedirectResponse
    {
        $action->execute($exam, $request->reason());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('messages.grade_sheet_returned')]);

        return back();
    }
}
