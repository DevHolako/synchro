<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\SaveExamAction;
use App\Http\Controllers\Concerns\FlashesExamOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\UpdateExamRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;

/**
 * Edits a draft or scheduled exam; a scheduled one is checked afresh at its new time.
 */
class ExamUpdateController extends Controller
{
    use FlashesExamOutcome;

    public function __invoke(UpdateExamRequest $request, Exam $exam, SaveExamAction $action): RedirectResponse
    {
        $outcome = $action->execute($exam, $request->payload());

        $this->flashExamOutcome(__('messages.exam_updated'), $outcome['released']);

        return back();
    }
}
