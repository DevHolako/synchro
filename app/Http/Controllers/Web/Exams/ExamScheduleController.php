<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\ScheduleExamAction;
use App\Http\Controllers\Concerns\FlashesExamOutcome;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ExamScheduleController extends Controller
{
    use FlashesExamOutcome;

    public function __invoke(Exam $exam, ScheduleExamAction $action): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $outcome = $action->execute($exam);

        $this->flashExamOutcome(__('messages.exam_scheduled'), $outcome['released']);

        return back();
    }
}
